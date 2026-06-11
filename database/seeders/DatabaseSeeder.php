<?php

namespace Database\Seeders;

use App\Models\Assembly;
use App\Models\AssemblyCompany;
use App\Models\Company;
use App\Models\Election;
use App\Models\ElectionQuestion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        if (! app()->environment('local', 'testing')) {
            if (! $adminEmail || ! $adminPassword) {
                throw new RuntimeException('ADMIN_EMAIL et ADMIN_PASSWORD sont obligatoires hors environnement local.');
            }
        }

        $adminEmail ??= 'admin@eurocham.sn';
        $adminPassword ??= 'eurocham2026';

        // Admin back-office account. Production must provide ADMIN_PASSWORD in .env.
        User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrateur EUROCHAM',
                'password' => Hash::make($adminPassword),
            ],
        );

        $assembly = Assembly::query()->updateOrCreate(
            ['reference' => 'P01.EUROCHAM.2026'],
            [
                'name' => 'Assemblée Générale EUROCHAM 2026',
                'held_on' => '2026-06-18',
            ],
        );

        $members = [
            ['ACME Sénégal SA', true, true, false],
            ['Baobab Industries', true, true, false],
            ['Dakar Services', true, false, false],
            ['Teranga Consulting', false, false, true],
            ['SunuTech SARL', true, true, false],
        ];

        foreach ($members as [$name, $survey, $dues, $newMember]) {
            $company = Company::query()->updateOrCreate(
                ['normalized_name' => Company::normalizeName($name)],
                [
                    'name' => $name,
                    'survey_2025' => $survey,
                    'dues_2025' => $dues,
                    'new_member_2026' => $newMember,
                ],
            );

            AssemblyCompany::query()->updateOrCreate(
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
        }

        Election::current();

        $this->call(CandidateSeeder::class);

        $questionsVote = $assembly->elections()->firstOrCreate(
            ['type' => Election::TYPE_QUESTIONS, 'name' => 'Résolutions ordinaires'],
            [
                'status' => Election::STATUS_READY,
                'display_order' => 2,
                'current_round' => 1,
            ],
        );

        foreach ([
            ['Approbation du rapport moral', 'Validation du rapport moral présenté à l’Assemblée Générale.'],
            ['Approbation des comptes', 'Validation des comptes annuels présentés par le trésorier.'],
        ] as $index => [$title, $description]) {
            ElectionQuestion::query()->updateOrCreate(
                ['election_id' => $questionsVote->id, 'title' => $title],
                [
                    'description' => $description,
                    'display_order' => $index + 1,
                ],
            );
        }
    }
}
