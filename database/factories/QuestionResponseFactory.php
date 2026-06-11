<?php

namespace Database\Factories;

use App\Models\ElectionQuestion;
use App\Models\QuestionResponse;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionResponse>
 */
class QuestionResponseFactory extends Factory
{
    protected $model = QuestionResponse::class;

    public function definition(): array
    {
        return [
            'vote_id' => Vote::factory(),
            'election_question_id' => ElectionQuestion::factory(),
            'answer' => fake()->randomElement([true, false, null]),
        ];
    }
}
