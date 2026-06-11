<?php

namespace Database\Factories;

use App\Models\AssemblyCompany;
use App\Models\Election;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    public function definition(): array
    {
        $election = Election::current();
        $assemblyCompany = AssemblyCompany::factory()->create(['assembly_id' => $election->assembly_id]);

        return [
            'election_id' => $election->id,
            'company_id' => $assemblyCompany->company_id,
            'assembly_company_id' => $assemblyCompany->id,
            'round' => 1,
            'is_proxy' => false,
            'reference_number' => 'TEST-'.Str::upper(Str::random(8)),
            'voted_at' => now(),
        ];
    }
}
