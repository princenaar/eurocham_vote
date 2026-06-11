<?php

namespace Database\Factories;

use App\Models\Assembly;
use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Election>
 */
class ElectionFactory extends Factory
{
    protected $model = Election::class;

    public function definition(): array
    {
        return [
            'assembly_id' => Assembly::factory(),
            'name' => fake()->sentence(4),
            'type' => Election::TYPE_BOARD,
            'status' => Election::STATUS_DRAFT,
            'candidate_threshold' => 20,
            'current_round' => 1,
            'display_order' => 1,
            'window_open' => false,
            'qr_active' => false,
        ];
    }

    public function questions(): static
    {
        return $this->state(fn () => [
            'type' => Election::TYPE_QUESTIONS,
            'mode' => null,
        ]);
    }
}
