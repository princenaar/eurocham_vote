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

        $eligibleCount = Company::query()
            ->where(fn ($q) => $q->where('survey_2025', true)
                ->orWhere('dues_2025', true)
                ->orWhere('new_member_2026', true))
            ->count();

        $companyCount = Company::query()->count();
        $votesCast = Vote::query()->count();

        // Participation is measured against eligible companies (one vote each — rule 1).
        $participation = $eligibleCount > 0
            ? round($votesCast / $eligibleCount * 100, 1)
            : 0.0;

        return view('admin.dashboard', [
            'election' => $election,
            'companyCount' => $companyCount,
            'eligibleCount' => $eligibleCount,
            'candidateCount' => Candidate::query()->count(),
            'votesCast' => $votesCast,
            'participation' => $participation,
        ]);
    }
}
