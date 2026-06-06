<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ResultsExport;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Vote;
use App\Support\AuditLogger;
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
     * Shared result set: candidates ranked by vote count plus turnout figures.
     *
     * @return array<string, mixed>
     */
    private function data(): array
    {
        $election = Election::current();

        $results = Candidate::query()
            ->withCount('selections')
            ->orderByDesc('selections_count')
            ->orderBy('name')
            ->get();

        return [
            'election' => $election,
            'results' => $results,
            'votesCast' => Vote::query()->count(),
            'generatedAt' => now(),
        ];
    }
}
