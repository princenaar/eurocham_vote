<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'election_id' => Election::current()->id,
            'assembly_company_id' => fn () => Election::current()->assembly->eligibleCompanies()->value('id'),
            'name' => fake()->unique()->name(),
            'display_order' => fake()->numberBetween(1, 200),
            'auto_elected' => false,
        ];
    }

    public function ordered(int $order): static
    {
        return $this->state(fn () => [
            'display_order' => $order,
        ]);
    }
}
