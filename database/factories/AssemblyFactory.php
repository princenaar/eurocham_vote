<?php

namespace Database\Factories;

use App\Models\Assembly;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assembly>
 */
class AssemblyFactory extends Factory
{
    protected $model = Assembly::class;

    public function definition(): array
    {
        return [
            'name' => 'Assemblée Générale '.fake()->year(),
            'reference' => fake()->unique()->bothify('AG-####'),
            'held_on' => fake()->date(),
        ];
    }
}
