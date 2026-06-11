<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Election;
use App\Support\AuditLogger;
use App\Support\ElectionResults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ElectionController extends Controller
{
    public function edit(Request $request): View
    {
        $election = $this->selectedElection($request);

        return view('admin.election.edit', [
            'assembly' => $election->assembly,
            'elections' => $election->assembly->elections()->get(),
            'election' => $election,
            'candidateCount' => $election->candidates()->count(),
            'questionCount' => $election->questions()->count(),
            'voteUrl' => route('vote.start'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $election = $this->selectedElection($request);

        if (! $election->canEditConfiguration()) {
            return redirect()->route('admin.election.edit', ['election' => $election->id])
                ->withErrors(['election' => 'Les paramètres du scrutin sont verrouillés dès l’ouverture du vote.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'candidate_threshold' => [$election->isBoardVote() ? 'required' : 'nullable', 'integer', 'min:1', 'max:200'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:500'],
        ], [
            'name.required' => 'Le nom du scrutin est obligatoire.',
            'candidate_threshold.required' => 'Le nombre de sièges est obligatoire.',
        ]);

        if (! $election->isBoardVote()) {
            unset($data['candidate_threshold']);
        }

        $election->update($data);
        if ($election->isBoardVote()) {
            // Threshold change can flip Mode A/B (rule 4).
            $election->syncModeFromCandidates();
        }
        AuditLogger::log('election.updated', 'Paramètres du scrutin modifiés', $data);

        return redirect()->route('admin.election.edit', ['election' => $election->id])
            ->with('status', 'Paramètres enregistrés.');
    }

    public function toggleWindow(Request $request): RedirectResponse
    {
        $election = $this->selectedElection($request);
        $open = ! $election->window_open;

        if ($open && ($active = Election::active()) && ! $active->is($election)) {
            return redirect()->route('admin.election.edit', ['election' => $election->id])
                ->withErrors(['window' => "Impossible d’ouvrir ce vote : « {$active->name} » est déjà actif."]);
        }

        if ($open && ! $election->canOpen()) {
            return redirect()->route('admin.election.edit', ['election' => $election->id])
                ->withErrors(['window' => 'Impossible d’ouvrir le vote : vérifiez le QR code, les candidats, le mode et la liste des entreprises éligibles.']);
        }

        $election->window_open = $open;
        if ($open) {
            $election->status = Election::STATUS_OPEN;
            $election->opened_at = now();
            $election->active_slot = Election::ACTIVE_SLOT_GLOBAL;
        } else {
            $election->status = Election::STATUS_CLOSED;
            $election->closed_at = now();
            $election->active_slot = null;
        }
        $election->save();

        AuditLogger::log(
            $open ? 'election.window_opened' : 'election.window_closed',
            $open ? 'Ouverture du vote' : 'Clôture du vote',
        );

        return redirect()->route('admin.election.edit', ['election' => $election->id])
            ->with('status', $open ? 'Le vote est OUVERT.' : 'Le vote est CLÔTURÉ.');
    }

    public function toggleQr(Request $request): RedirectResponse
    {
        $election = $this->selectedElection($request);
        $election->qr_active = ! $election->qr_active;
        $election->save();

        AuditLogger::log($election->qr_active ? 'election.qr_activated' : 'election.qr_deactivated');

        return redirect()->route('admin.election.edit', ['election' => $election->id])
            ->with('status', $election->qr_active ? 'QR code activé.' : 'QR code désactivé.');
    }

    /**
     * Launch a tiebreaker runoff (vote de départage) for a boundary tie. Re-opens a
     * restricted scrutin among only the tied candidates for the remaining seats, as a
     * new round so each company may vote once more (rule 1). Only meaningful once the
     * current round is closed and an ambiguous tie exists.
     */
    public function launchRunoff(Request $request): RedirectResponse
    {
        $election = $this->selectedElection($request);

        if (! $election->canLaunchRunoff()) {
            return redirect()->route('admin.results.index', ['election' => $election->id])
                ->withErrors(['runoff' => 'Clôturez d’abord le vote en cours avant de lancer un départage.']);
        }

        if (($active = Election::active()) && ! $active->is($election)) {
            return redirect()->route('admin.results.index', ['election' => $election->id])
                ->withErrors(['runoff' => "Impossible de lancer le départage : « {$active->name} » est déjà actif."]);
        }

        $tie = ElectionResults::for($election)->pendingTie();

        if ($tie === null) {
            return redirect()->route('admin.results.index', ['election' => $election->id])
                ->withErrors(['runoff' => 'Aucune égalité à départager.']);
        }

        $tiedIds = $tie['tied']->pluck('id')->all();

        $election->update([
            'current_round' => $election->current_round + 1,
            'runoff_candidate_ids' => $tiedIds,
            'runoff_seats' => $tie['seats'],
            'status' => Election::STATUS_RUNOFF_OPEN,
            'window_open' => true,
            'qr_active' => true,
            'active_slot' => Election::ACTIVE_SLOT_GLOBAL,
            'opened_at' => now(),
        ]);

        AuditLogger::log('election.runoff_launched', 'Vote de départage lancé', [
            'round' => $election->current_round,
            'seats' => $tie['seats'],
            'candidate_ids' => $tiedIds,
        ]);

        return redirect()->route('admin.election.edit', $election->is(Election::current()) ? [] : ['election' => $election->id])
            ->with('status', "Vote de départage lancé (tour {$election->current_round}).");
    }

    /**
     * QR code (SVG — no image extension required) pointing at the public voter URL.
     */
    public function qr(): Response
    {
        $svg = QrCode::format('svg')->size(320)->margin(1)->generate(route('vote.start'));

        return response((string) $svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function qrFullscreen(): View
    {
        return view('admin.election.qr-fullscreen', [
            'voteUrl' => route('vote.start'),
        ]);
    }

    private function selectedElection(Request $request): Election
    {
        $id = $request->input('election') ?? $request->input('election_id');

        if ($id) {
            return Election::query()->with('assembly')->findOrFail($id);
        }

        return Election::current()->load('assembly');
    }
}
