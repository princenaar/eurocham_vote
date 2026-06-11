<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assembly;
use App\Models\Election;
use App\Models\Vote;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $election = Election::current();
        $assembly = $election->assembly ?? Assembly::current();

        $eligibleCount = $assembly->eligibleCompanies()->count();

        $companyCount = $assembly->companies()->count();
        $mainVotesCast = Vote::query()->forElection($election)->round(1)->count();
        $currentRoundVotesCast = Vote::query()->forElection($election)->round($election->current_round)->count();

        // Participation is measured against eligible companies (one vote each — rule 1).
        $mainParticipation = $eligibleCount > 0
            ? round($mainVotesCast / $eligibleCount * 100, 1)
            : 0.0;

        $currentRoundParticipation = $eligibleCount > 0
            ? round($currentRoundVotesCast / $eligibleCount * 100, 1)
            : 0.0;

        return view('admin.dashboard', [
            'election' => $election,
            'assembly' => $assembly,
            'companyCount' => $companyCount,
            'eligibleCount' => $eligibleCount,
            'candidateCount' => $election->candidates()->count(),
            'votesCast' => $mainVotesCast,
            'mainVotesCast' => $mainVotesCast,
            'currentRoundVotesCast' => $currentRoundVotesCast,
            'participation' => $mainParticipation,
            'mainParticipation' => $mainParticipation,
            'currentRoundParticipation' => $currentRoundParticipation,
        ]);
    }
}
