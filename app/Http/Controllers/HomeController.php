<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\View\View;

/**
 * Public landing page: introduces EUROCHAM, the Assemblée Générale, and the voting app.
 * Thin by design — only resolves the current scrutin so the hero CTA reflects live state
 * (voting open / not yet open / results published). All electoral rules stay server-side
 * in their respective controllers.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        $election = Election::current();

        // State-aware CTA. Mirrors the gates used by VoteController / ResultsController.
        if ($election->isVotingOpen()) {
            $cta = ['label' => 'Accéder au vote', 'route' => route('vote.start'), 'state' => 'open'];
        } elseif ($election->canExportFinalResults()) {
            $cta = ['label' => 'Voir les résultats', 'route' => route('results.public'), 'state' => 'closed'];
        } else {
            $cta = ['label' => 'Suivre le scrutin', 'route' => route('results.public'), 'state' => 'pending'];
        }

        return view('home', [
            'election' => $election,
            'cta' => $cta,
        ]);
    }
}
