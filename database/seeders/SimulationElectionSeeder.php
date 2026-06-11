<?php

namespace Database\Seeders;

use App\Models\Assembly;
use App\Models\AssemblyCompany;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\ElectionQuestion;
use App\Models\QuestionResponse;
use App\Models\Vote;
use App\Models\VoteSelection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimulationElectionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $assembly = Assembly::query()->updateOrCreate(
                ['reference' => 'SIM.EUROCHAM.2026'],
                [
                    'name' => 'Simulation — Assemblée Générale EUROCHAM 2026',
                    'held_on' => now()->toDateString(),
                ],
            );

            $boardVote = $assembly->elections()->updateOrCreate(
                ['type' => Election::TYPE_BOARD, 'name' => 'Simulation — Conseil d’Administration'],
                [
                    'status' => Election::STATUS_CLOSED,
                    'candidate_threshold' => 20,
                    'current_round' => 1,
                    'window_open' => false,
                    'qr_active' => true,
                    'active_slot' => null,
                    'opened_at' => now()->subMinutes(35),
                    'closed_at' => now()->subMinutes(5),
                    'display_order' => 1,
                ],
            );

            $candidates = collect([
                'Amadou Ba', 'Awa Diop', 'Mamadou Ndiaye', 'Fatou Sow', 'Cheikh Diagne',
                'Sophie Martin', 'Jean Dupont', 'Marie Laurent', 'Ibrahima Fall', 'Ndeye Gueye',
                'Ousmane Sarr', 'Claire Bernard', 'Moussa Kane', 'Aminata Diallo', 'Pierre Moreau',
                'Elisabeth Petit', 'Abdoulaye Seck', 'Bineta Mbaye', 'Karim Cisse', 'Helene Robert',
                'Alioune Faye', 'Caroline Durand', 'Serigne Thiam', 'Nafi Sy',
            ])->map(fn (string $name, int $index) => Candidate::updateOrCreate(
                ['election_id' => $boardVote->id, 'name' => "Simulation - {$name}"],
                ['display_order' => $index + 1, 'auto_elected' => false],
            ))->values();

            $boardVote->syncModeFromCandidates();
            $boardVote->update([
                'status' => Election::STATUS_CLOSED,
                'window_open' => false,
                'qr_active' => true,
                'closed_at' => now()->subMinutes(5),
            ]);

            $assemblyCompanies = collect(range(1, 30))->map(function (int $index) use ($assembly) {
                $name = sprintf('Simulation - Entreprise %02d', $index);

                $company = Company::updateOrCreate(
                    ['normalized_name' => Company::normalizeName($name)],
                    [
                        'name' => $name,
                        'survey_2025' => $index % 4 !== 0,
                        'dues_2025' => $index % 5 !== 0,
                        'new_member_2026' => $index > 26,
                    ],
                );

                return AssemblyCompany::updateOrCreate(
                    ['assembly_id' => $assembly->id, 'company_id' => $company->id],
                    [
                        'name' => $company->name,
                        'normalized_name' => $company->normalized_name,
                        'survey_2025' => $company->survey_2025,
                        'dues_2025' => $company->dues_2025,
                        'new_member_2026' => $company->new_member_2026,
                        'eligible' => $company->isEligible(),
                    ],
                );
            })->values();

            $candidateStructures = $assemblyCompanies->where('eligible', true)->values();
            $candidates->each(function (Candidate $candidate, int $index) use ($candidateStructures) {
                if ($candidateStructures->isNotEmpty()) {
                    $candidate->update([
                        'assembly_company_id' => $candidateStructures[$index % $candidateStructures->count()]->id,
                    ]);
                }
            });

            $candidateIds = $candidates->pluck('id')->all();

            $assemblyCompanies->where('eligible', true)->take(22)->values()
                ->each(function (AssemblyCompany $assemblyCompany, int $index) use ($boardVote, $candidateIds) {
                    $reference = sprintf('SIM-EC2026-%03d', $index + 1);

                    $vote = Vote::firstOrCreate(
                        ['election_id' => $boardVote->id, 'company_id' => $assemblyCompany->company_id, 'round' => 1],
                        [
                            'assembly_company_id' => $assemblyCompany->id,
                            'is_proxy' => ($index + 1) % 7 === 0,
                            'reference_number' => $reference,
                            'voted_at' => now()->subMinutes(30 - $index),
                        ],
                    );

                    $offset = $index % count($candidateIds);
                    $rotated = collect(array_merge(
                        array_slice($candidateIds, $offset),
                        array_slice($candidateIds, 0, $offset),
                    ))->take(20)->values();

                    VoteSelection::query()->insertOrIgnore($rotated->map(fn (int $candidateId) => [
                        'vote_id' => $vote->id,
                        'candidate_id' => $candidateId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->all());
                });

            $questionsVote = $assembly->elections()->updateOrCreate(
                ['type' => Election::TYPE_QUESTIONS, 'name' => 'Simulation — Résolutions'],
                [
                    'status' => Election::STATUS_CLOSED,
                    'display_order' => 2,
                    'window_open' => false,
                    'qr_active' => true,
                    'closed_at' => now()->subMinutes(3),
                ],
            );

            $questions = collect([
                'Approuver le rapport moral',
                'Approuver les comptes annuels',
            ])->map(fn (string $title, int $index) => ElectionQuestion::updateOrCreate(
                ['election_id' => $questionsVote->id, 'title' => $title],
                ['description' => null, 'display_order' => $index + 1],
            ));

            $assemblyCompanies->where('eligible', true)->take(18)->values()
                ->each(function (AssemblyCompany $assemblyCompany, int $index) use ($questionsVote, $questions) {
                    $vote = Vote::firstOrCreate(
                        ['election_id' => $questionsVote->id, 'company_id' => $assemblyCompany->company_id, 'round' => 1],
                        [
                            'assembly_company_id' => $assemblyCompany->id,
                            'reference_number' => sprintf('SIM-Q-%03d', $index + 1),
                            'voted_at' => now()->subMinutes(20 - $index),
                        ],
                    );

                    foreach ($questions as $questionIndex => $question) {
                        QuestionResponse::updateOrCreate(
                            ['vote_id' => $vote->id, 'election_question_id' => $question->id],
                            ['answer' => ($index + $questionIndex) % 5 === 0 ? null : (($index + $questionIndex) % 3 !== 0)],
                        );
                    }
                });
        });
    }
}
