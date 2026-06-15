<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Election;
use App\Support\ElectionResults;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public, read-only results / in-room proclamation (CLAUDE.md rules 7 & 8). Individual
 * choices are never revealed while voting is in progress; the consolidated results are
 * disclosed automatically once the admin closes the window. The page polls itself so a
 * projected display flips from "scrutin en cours" to the results without a manual reload.
 */
class ResultsController extends Controller
{
    public function index(Request $request): View
    {
        $election = $this->selectedElection($request);

        // Reveal only after a real close. While the window is open (incl. a live runoff)
        // or the scrutin has never been closed, show turnout only — never choices (rule 7).
        if ($election->window_open || ! $election->canExportFinalResults()) {
            return view('vote.results-pending', [
                'election' => $election,
                'votesCast' => $election->votes()->round($election->current_round)->count(),
                'eligibleCount' => $this->eligibleCount(),
                'isRunoff' => $election->isRunoff(),
            ]);
        }

        $results = ElectionResults::for($election);

        if ($election->isQuestionsVote()) {
            return view('vote.results-questions', [
                'election' => $election,
                'questionResults' => $results->questionResults(),
                'votesCast' => $results->votesCast(1),
            ]);
        }

        return view('vote.results', [
            'election' => $election,
            'ranking' => $election->mode === Election::MODE_AUTO ? null : $results->mainRanking(),
            'electedBoard' => $results->electedBoard(),
            'electedIds' => $results->electedBoard()->pluck('id')->all(),
            'hasUnresolvedTie' => $results->hasUnresolvedTie(),
            'pendingTie' => $results->pendingTie(),
            'isRunoff' => $election->isRunoff(),
            'runoffRounds' => $results->runoffRounds(),
            'votesCast' => $results->votesCast(1),
        ]);
    }

    private function eligibleCount(): int
    {
        return $this->selectedElection(request())->assembly->eligibleCompanies()->count();
    }

    private function selectedElection(Request $request): Election
    {
        $id = $request->input('election');

        if ($id) {
            return Election::query()->with('assembly')->findOrFail($id);
        }

        return Election::active()
            ?? Election::query()->with('assembly')->where('status', Election::STATUS_CLOSED)->latest('closed_at')->first()
            ?? Election::current()->load('assembly');
    }
}
