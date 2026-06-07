<?php

namespace App\Exports;

use App\Models\Election;
use App\Support\ElectionResults;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Per-candidate results for the official Excel report (CLAUDE.md rule 8): main-vote
 * ranking with the consolidated "elected" flag from the single ElectionResults source.
 */
class ResultsExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var array<int, int> */
    private array $electedIds;

    public function __construct()
    {
        $this->electedIds = ElectionResults::for(Election::current())->electedBoard()->pluck('id')->all();
    }

    public function collection(): Collection
    {
        return ElectionResults::for(Election::current())->mainRanking();
    }

    public function headings(): array
    {
        return ['Rang', 'Candidat', 'Voix', 'Élu'];
    }

    /**
     * @param  array{candidate: \App\Models\Candidate, votes: int, rank: int}  $row
     */
    public function map($row): array
    {
        return [
            $row['rank'],
            $row['candidate']->name,
            $row['votes'],
            in_array($row['candidate']->id, $this->electedIds, true) ? 'Oui' : 'Non',
        ];
    }
}
