<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\Vote;
use App\Models\VoteSelection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimulationElectionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $election = Election::current();
            $election->update([
                'name' => 'Simulation — Assemblée Générale EUROCHAM 2026',
                'status' => Election::STATUS_CLOSED,
                'candidate_threshold' => 20,
                'current_round' => 1,
                'runoff_candidate_ids' => null,
                'runoff_seats' => null,
                'window_open' => false,
                'qr_active' => true,
                'opened_at' => now()->subMinutes(35),
                'closed_at' => now()->subMinutes(5),
            ]);

            $candidates = collect([
                'Amadou Ba',
                'Awa Diop',
                'Mamadou Ndiaye',
                'Fatou Sow',
                'Cheikh Diagne',
                'Sophie Martin',
                'Jean Dupont',
                'Marie Laurent',
                'Ibrahima Fall',
                'Ndeye Gueye',
                'Ousmane Sarr',
                'Claire Bernard',
                'Moussa Kane',
                'Aminata Diallo',
                'Pierre Moreau',
                'Elisabeth Petit',
                'Abdoulaye Seck',
                'Bineta Mbaye',
                'Karim Cisse',
                'Helene Robert',
                'Alioune Faye',
                'Caroline Durand',
                'Serigne Thiam',
                'Nafi Sy',
            ])->map(fn (string $name, int $index) => Candidate::updateOrCreate(
                ['name' => "Simulation - {$name}"],
                ['display_order' => $index + 1, 'auto_elected' => false],
            ))->values();

            $election->syncModeFromCandidates();
            $election->update([
                'status' => Election::STATUS_CLOSED,
                'window_open' => false,
                'qr_active' => true,
                'closed_at' => now()->subMinutes(5),
            ]);

            $companies = collect(range(1, 30))->map(function (int $index) {
                $name = sprintf('Simulation - Entreprise %02d', $index);

                return Company::updateOrCreate(
                    ['normalized_name' => Company::normalizeName($name)],
                    [
                        'name' => $name,
                        'survey_2025' => $index % 4 !== 0,
                        'dues_2025' => $index % 5 !== 0,
                        'new_member_2026' => $index > 26,
                    ],
                );
            })->values();

            $candidateIds = $candidates->pluck('id')->all();

            $companies->take(22)->each(function (Company $company, int $index) use ($candidateIds) {
                $reference = sprintf('SIM-EC2026-%03d', $index + 1);

                $vote = Vote::firstOrCreate(
                    ['company_id' => $company->id, 'round' => 1],
                    [
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

                $rows = $rotated->map(fn (int $candidateId) => [
                    'vote_id' => $vote->id,
                    'candidate_id' => $candidateId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                VoteSelection::query()->insertOrIgnore($rows);
            });
        });
    }
}
