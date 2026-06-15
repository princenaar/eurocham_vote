<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ResultsExport;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Support\AuditLogger;
use App\Support\ElectionResults;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.results.index', $this->data($this->selectedElection($request)));
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $election = $this->selectedElection($request);
        $this->ensureFinalExportAllowed($election);
        AuditLogger::log('results.export', 'Export Excel des résultats', ['election_id' => $election->id]);

        return Excel::download(new ResultsExport($election), 'resultats_eurocham_2026.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        $election = $this->selectedElection($request);
        $this->ensureFinalExportAllowed($election);
        AuditLogger::log('results.export', 'Export PDF des résultats', ['election_id' => $election->id]);

        $pdf = Pdf::loadView('admin.results.pdf', $this->data($election));

        return $pdf->download('resultats_eurocham_2026.pdf');
    }

    /**
     * Shared result set: round-1 ranking, the consolidated elected Board, any pending
     * boundary tie, and the live runoff ranking — all from the single ElectionResults
     * source so the screen, Excel and PDF can never disagree.
     *
     * @return array<string, mixed>
     */
    private function data(Election $election): array
    {
        $results = ElectionResults::for($election);

        return [
            'election' => $election,
            'elections' => $election->assembly->elections()->get(),
            'ranking' => $election->isBoardVote() ? $results->mainRanking() : null,
            'electedIds' => $results->electedBoard()->pluck('id')->all(),
            'electedBoard' => $results->electedBoard(),
            'hasUnresolvedTie' => $results->hasUnresolvedTie(),
            'pendingTie' => $results->pendingTie(),
            'isRunoff' => $election->isRunoff(),
            'runoffRounds' => $election->isBoardVote() ? $results->runoffRounds() : collect(),
            'votesCast' => $results->votesCast(1),
            'questionResults' => $election->isQuestionsVote() ? $results->questionResults() : null,
            'generatedAt' => now(),
            'canExportFinalResults' => $election->canExportFinalResults(),
        ];
    }

    private function ensureFinalExportAllowed(Election $election): void
    {
        abort_unless($election->canExportFinalResults(), 403, 'Les exports définitifs sont disponibles uniquement après clôture du scrutin.');
    }

    private function selectedElection(Request $request): Election
    {
        $id = $request->input('election');

        if ($id) {
            return Election::query()->with('assembly')->findOrFail($id);
        }

        return Election::current()->load('assembly');
    }
}
