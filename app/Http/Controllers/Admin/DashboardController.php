<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\Vote;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $election = Election::current();

        $eligibleCount = Company::eligible()->count();

        $companyCount = Company::query()->count();
        $mainVotesCast = Vote::round(1)->count();
        $currentRoundVotesCast = Vote::round($election->current_round)->count();

        // Participation is measured against eligible companies (one vote each — rule 1).
        $mainParticipation = $eligibleCount > 0
            ? round($mainVotesCast / $eligibleCount * 100, 1)
            : 0.0;

        $currentRoundParticipation = $eligibleCount > 0
            ? round($currentRoundVotesCast / $eligibleCount * 100, 1)
            : 0.0;

        return view('admin.dashboard', [
            'election' => $election,
            'companyCount' => $companyCount,
            'eligibleCount' => $eligibleCount,
            'candidateCount' => Candidate::query()->count(),
            'votesCast' => $mainVotesCast,
            'mainVotesCast' => $mainVotesCast,
            'currentRoundVotesCast' => $currentRoundVotesCast,
            'participation' => $mainParticipation,
            'mainParticipation' => $mainParticipation,
            'currentRoundParticipation' => $currentRoundParticipation,
        ]);
    }
}
