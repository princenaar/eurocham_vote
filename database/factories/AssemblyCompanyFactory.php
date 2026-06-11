<?php

namespace Database\Factories;

use App\Models\Assembly;
use App\Models\AssemblyCompany;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssemblyCompany>
 */
class AssemblyCompanyFactory extends Factory
{
    protected $model = AssemblyCompany::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'assembly_id' => Assembly::factory(),
            'company_id' => Company::query()->create([
                'name' => $name,
                'normalized_name' => Company::normalizeName($name),
                'survey_2025' => true,
                'dues_2025' => true,
            ])->id,
            'name' => $name,
            'normalized_name' => Company::normalizeName($name),
            'survey_2025' => true,
            'dues_2025' => true,
            'new_member_2026' => false,
            'eligible' => true,
        ];
    }
}
