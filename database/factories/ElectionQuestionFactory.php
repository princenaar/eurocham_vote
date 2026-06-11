<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\ElectionQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ElectionQuestion>
 */
class ElectionQuestionFactory extends Factory
{
    protected $model = ElectionQuestion::class;

    public function definition(): array
    {
        return [
            'election_id' => Election::query()->where('type', Election::TYPE_QUESTIONS)->value('id')
                ?? Election::current()->assembly->elections()->create([
                    'name' => 'Vote par questions',
                    'type' => Election::TYPE_QUESTIONS,
                    'status' => Election::STATUS_READY,
                    'display_order' => 2,
                ])->id,
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'display_order' => fake()->numberBetween(1, 20),
        ];
    }
}
