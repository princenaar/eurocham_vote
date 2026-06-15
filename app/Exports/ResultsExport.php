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
 * and runoff rankings with the consolidated "elected" flag from ElectionResults.
 */
class ResultsExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var array<int, int> */
    private array $electedIds;

    public function __construct(private readonly Election $election)
    {
        $this->electedIds = $this->election->isBoardVote()
            ? ElectionResults::for($this->election)->electedBoard()->pluck('id')->all()
            : [];
    }

    public function collection(): Collection
    {
        $results = ElectionResults::for($this->election);

        if ($this->election->isQuestionsVote()) {
            return $results->questionResults();
        }

        $mainRows = $results->mainRanking()
            ->map(fn (array $row) => ['round_label' => 'Tour principal'] + $row);

        $runoffRows = $results->runoffRounds()
            ->flatMap(fn (array $runoffRound) => $runoffRound['ranking']
                ->map(fn (array $row) => ['round_label' => 'Tour '.$runoffRound['round']] + $row));

        return $mainRows->concat($runoffRows)->values();
    }

    public function headings(): array
    {
        if ($this->election->isQuestionsVote()) {
            return ['Question', 'Oui', 'Non', 'Abstention', '% Oui', '% Non', 'Résultat'];
        }

        return ['Tour', 'Rang', 'Candidat', 'Structure', 'Voix', 'Élu'];
    }

    /**
     * @param  array{candidate: \App\Models\Candidate, votes: int, rank: int}  $row
     */
    public function map($row): array
    {
        if ($this->election->isQuestionsVote()) {
            return [
                $row['question']->title,
                $row['yes'],
                $row['no'],
                $row['abstain'],
                $row['yes_percent'].'%',
                $row['no_percent'].'%',
                $row['result'],
            ];
        }

        return [
            $row['round_label'] ?? 'Tour principal',
            $row['rank'],
            $row['candidate']->name,
            $row['candidate']->assemblyCompany?->name,
            $row['votes'],
            in_array($row['candidate']->id, $this->electedIds, true) ? 'Oui' : 'Non',
        ];
    }
}
