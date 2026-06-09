<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Election;
use App\Models\Vote;
use App\Support\ElectionResults;
use Illuminate\View\View;

/**
 * Public, read-only results / in-room proclamation (CLAUDE.md rules 7 & 8). Individual
 * choices are never revealed while voting is in progress; the consolidated results are
 * disclosed automatically once the admin closes the window. The page polls itself so a
 * projected display flips from "scrutin en cours" to the results without a manual reload.
 */
class ResultsController extends Controller
{
    public function index(): View
    {
        $election = Election::current();

        // Reveal only after a real close. While the window is open (incl. a live runoff)
        // or the scrutin has never been closed, show turnout only — never choices (rule 7).
        if ($election->window_open || ! $election->canExportFinalResults()) {
            return view('vote.results-pending', [
                'election' => $election,
                'votesCast' => Vote::round($election->current_round)->count(),
                'eligibleCount' => $this->eligibleCount(),
                'isRunoff' => $election->isRunoff(),
            ]);
        }

        $results = ElectionResults::for($election);

        return view('vote.results', [
            'election' => $election,
            'ranking' => $election->mode === Election::MODE_AUTO ? null : $results->mainRanking(),
            'electedBoard' => $results->electedBoard(),
            'electedIds' => $results->electedBoard()->pluck('id')->all(),
            'hasUnresolvedTie' => $results->hasUnresolvedTie(),
            'pendingTie' => $results->pendingTie(),
            'isRunoff' => $election->isRunoff(),
            'runoffRanking' => $election->isRunoff() ? $results->ranking($election->current_round) : null,
            'votesCast' => $results->votesCast(1),
        ]);
    }

    private function eligibleCount(): int
    {
        return Company::query()
            ->eligible()
            ->count();
    }
}
