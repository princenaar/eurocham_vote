<?php

use App\Models\Candidate;
use App\Models\Election;
use Database\Seeders\CandidateSeeder;

it('seeds twenty five ordered candidates and selects Mode A', function () {
    $this->seed(CandidateSeeder::class);

    expect(Candidate::query()->count())->toBe(25);
    expect(Candidate::query()->orderBy('display_order')->pluck('display_order')->all())
        ->toBe(range(1, 25));
    expect(Election::current()->fresh()->mode)->toBe(Election::MODE_SELECT);
});
