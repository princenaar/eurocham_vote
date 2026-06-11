<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Database\Seeder;

class CandidateSeeder extends Seeder
{
    public function run(): void
    {
        $election = Election::current();
        $structures = $election->assembly->eligibleCompanies()->orderBy('name')->get()->values();

        $names = [
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
            'Mame Coumba Gueye',
        ];

        foreach ($names as $index => $name) {
            Candidate::query()->updateOrCreate(
                ['election_id' => $election->id, 'name' => $name],
                [
                    'assembly_company_id' => $structures->isNotEmpty()
                        ? $structures[$index % $structures->count()]->id
                        : null,
                    'display_order' => $index + 1,
                    'auto_elected' => false,
                ],
            );
        }

        $election->syncModeFromCandidates();
    }
}
