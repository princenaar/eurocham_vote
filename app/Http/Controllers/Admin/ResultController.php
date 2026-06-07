<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ResultsExport;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Support\AuditLogger;
use App\Support\ElectionResults;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResultController extends Controller
{
    public function index(): View
    {
        return view('admin.results.index', $this->data());
    }

    public function exportExcel(): BinaryFileResponse
    {
        AuditLogger::log('results.export', 'Export Excel des résultats');

        return Excel::download(new ResultsExport(), 'resultats_eurocham_2026.xlsx');
    }

    public function exportPdf(): Response
    {
        AuditLogger::log('results.export', 'Export PDF des résultats');

        $pdf = Pdf::loadView('admin.results.pdf', $this->data());

        return $pdf->download('resultats_eurocham_2026.pdf');
    }

    /**
     * Shared result set: round-1 ranking, the consolidated elected Board, any pending
     * boundary tie, and the live runoff ranking — all from the single ElectionResults
     * source so the screen, Excel and PDF can never disagree.
     *
     * @return array<string, mixed>
     */
    private function data(): array
    {
        $election = Election::current();
        $results = ElectionResults::for($election);

        return [
            'election' => $election,
            'ranking' => $results->mainRanking(),
            'electedIds' => $results->electedBoard()->pluck('id')->all(),
            'electedBoard' => $results->electedBoard(),
            'hasUnresolvedTie' => $results->hasUnresolvedTie(),
            'pendingTie' => $results->pendingTie(),
            'isRunoff' => $election->isRunoff(),
            'runoffRanking' => $election->isRunoff() ? $results->ranking($election->current_round) : null,
            'votesCast' => $results->votesCast(1),
            'generatedAt' => now(),
        ];
    }
}
