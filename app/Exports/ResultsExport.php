<?php

namespace App\Exports;

use App\Models\Candidate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Per-candidate results for the official Excel report (CLAUDE.md rule 8).
 */
class ResultsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Candidate::query()
            ->withCount('selections')
            ->orderByDesc('selections_count')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return ['Rang', 'Candidat', 'Voix', 'Élu automatiquement'];
    }

    /**
     * @param  Candidate  $candidate
     */
    public function map($candidate): array
    {
        static $rank = 0;
        $rank++;

        return [
            $rank,
            $candidate->name,
            $candidate->selections_count,
            $candidate->auto_elected ? 'Oui' : 'Non',
        ];
    }
}
