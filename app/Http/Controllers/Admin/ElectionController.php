<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Election;
use App\Support\AuditLogger;
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
     * QR code (SVG — no image extension required) pointing at the public voter URL.
     */
    public function qr(): Response
    {
        $svg = QrCode::format('svg')->size(320)->margin(1)->generate(route('vote.start'));

        return response((string) $svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
