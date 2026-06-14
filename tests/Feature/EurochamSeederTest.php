<?php

use App\Models\Assembly;
use App\Models\Election;
use Database\Seeders\Eurocham2026Seeder;
use Database\Seeders\EurochamE2ESeeder;
use Illuminate\Support\Facades\Storage;

it('seeds the official EUROCHAM 2026 assembly votes companies and candidate avatars', function () {
    Storage::fake('public');

    $this->seed(Eurocham2026Seeder::class);

    $assembly = Assembly::query()->where('reference', 'P01.EUROCHAM.2026')->first();
    expect($assembly)->not->toBeNull();
    expect($assembly->held_on->toDateString())->toBe('2026-06-18');
    expect($assembly->companies()->count())->toBeGreaterThan(200);

    $votes = $assembly->elections()->get();
    expect($votes->pluck('name')->all())->toBe([
        'Vote 1 — Assemblée générale à titre extraordinaire',
        'Vote 2 — Assemblée générale à titre ordinaire',
        'Vote 3 — Renouvellement du Conseil d’Administration',
        'Vote 4 — Pouvoirs pour l’exécution des délibérations',
    ]);

    expect($votes[0]->questions()->count())->toBe(2);
    expect($votes[1]->questions()->count())->toBe(5);
    expect($votes[3]->questions()->count())->toBe(1);

    $boardVote = $votes[2]->fresh();
    expect($boardVote->mode)->toBe(Election::MODE_SELECT);
    expect($boardVote->candidate_threshold)->toBe(20);
    expect($boardVote->candidates()->count())->toBe(21);

    $candidate = $boardVote->candidates()->first();
    expect($candidate->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($candidate->photo_path);
});

it('seeds a browser-test Mode B board vote without replacing the official Mode A vote', function () {
    Storage::fake('public');

    $this->seed(EurochamE2ESeeder::class);

    $assembly = Assembly::query()->where('reference', 'P01.EUROCHAM.2026')->firstOrFail();
    $officialBoard = $assembly->elections()
        ->where('name', 'Vote 3 — Renouvellement du Conseil d’Administration')
        ->firstOrFail();
    $modeB = $assembly->elections()
        ->where('name', 'E2E — Conseil d’Administration Mode B')
        ->firstOrFail();

    expect($officialBoard->fresh()->mode)->toBe(Election::MODE_SELECT);
    expect($modeB->fresh()->mode)->toBe(Election::MODE_AUTO);
    expect($modeB->candidates()->count())->toBe(5);
    expect($assembly->elections()->where('qr_active', false)->count())->toBe(0);
});
