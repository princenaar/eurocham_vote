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
    public function edit(): View
    {
        return view('admin.election.edit', [
            'election' => Election::current(),
            'candidateCount' => Candidate::query()->count(),
            'voteUrl' => route('vote.start'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'candidate_threshold' => ['required', 'integer', 'min:1', 'max:200'],
        ], [
            'name.required' => 'Le nom du scrutin est obligatoire.',
            'candidate_threshold.required' => 'Le nombre de sièges est obligatoire.',
        ]);

        $election = Election::current();
        $election->update($data);
        // Threshold change can flip Mode A/B (rule 4).
        $election->syncModeFromCandidates();
        AuditLogger::log('election.updated', 'Paramètres du scrutin modifiés', $data);

        return redirect()->route('admin.election.edit')->with('status', 'Paramètres enregistrés.');
    }

    public function toggleWindow(Request $request): RedirectResponse
    {
        $election = Election::current();
        $open = ! $election->window_open;

        $election->window_open = $open;
        $open ? $election->opened_at = now() : $election->closed_at = now();
        $election->save();

        AuditLogger::log(
            $open ? 'election.window_opened' : 'election.window_closed',
            $open ? 'Ouverture du vote' : 'Clôture du vote',
        );

        return redirect()->route('admin.election.edit')
            ->with('status', $open ? 'Le vote est OUVERT.' : 'Le vote est CLÔTURÉ.');
    }

    public function toggleQr(): RedirectResponse
    {
        $election = Election::current();
        $election->qr_active = ! $election->qr_active;
        $election->save();

        AuditLogger::log($election->qr_active ? 'election.qr_activated' : 'election.qr_deactivated');

        return redirect()->route('admin.election.edit')
            ->with('status', $election->qr_active ? 'QR code activé.' : 'QR code désactivé.');
    }

    /**
     * Launch a tiebreaker runoff (vote de départage) for a boundary tie. Re-opens a
     * restricted scrutin among only the tied candidates for the remaining seats, as a
     * new round so each company may vote once more (rule 1). Only meaningful once the
     * current round is closed and an ambiguous tie exists.
     */
    public function launchRunoff(): RedirectResponse
    {
        $election = Election::current();

        if ($election->window_open) {
            return redirect()->route('admin.results.index')
                ->withErrors(['runoff' => 'Clôturez d’abord le vote en cours avant de lancer un départage.']);
        }

        $tie = ElectionResults::for($election)->pendingTie();

        if ($tie === null) {
            return redirect()->route('admin.results.index')
                ->withErrors(['runoff' => 'Aucune égalité à départager.']);
        }

        $tiedIds = $tie['tied']->pluck('id')->all();

        $election->update([
            'current_round' => $election->current_round + 1,
            'runoff_candidate_ids' => $tiedIds,
            'runoff_seats' => $tie['seats'],
            'window_open' => true,
            'qr_active' => true,
            'opened_at' => now(),
        ]);

        AuditLogger::log('election.runoff_launched', 'Vote de départage lancé', [
            'round' => $election->current_round,
            'seats' => $tie['seats'],
            'candidate_ids' => $tiedIds,
        ]);

        return redirect()->route('admin.election.edit')
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
}
