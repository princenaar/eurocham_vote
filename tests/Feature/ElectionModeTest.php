<?php

use App\Models\Candidate;
use App\Models\Election;

/**
 * Scrutin mode auto-selection (CLAUDE.md rule 4) — correctness-critical.
 */

it('leaves mode undetermined with no candidates', function () {
    $election = Election::current();
    $election->syncModeFromCandidates();

    expect($election->fresh()->mode)->toBeNull();
});

it('selects Mode B and auto-elects all when candidates <= threshold', function () {
    $election = Election::current();
    $election->update(['candidate_threshold' => 20]);

    foreach (range(1, 20) as $i) {
        Candidate::create(['name' => "Candidat {$i}"]);
    }
    $election->syncModeFromCandidates();

    expect($election->fresh()->mode)->toBe(Election::MODE_AUTO);
    expect(Candidate::where('auto_elected', true)->count())->toBe(20);
});

it('selects Mode A and elects no one automatically when candidates > threshold', function () {
    $election = Election::current();
    $election->update(['candidate_threshold' => 20]);

    foreach (range(1, 21) as $i) {
        Candidate::create(['name' => "Candidat {$i}"]);
    }
    $election->syncModeFromCandidates();

    expect($election->fresh()->mode)->toBe(Election::MODE_SELECT);
    expect(Candidate::where('auto_elected', true)->count())->toBe(0);
});

it('re-syncs mode automatically when a candidate is added via the admin', function () {
    $admin = \App\Models\User::factory()->create();
    Election::current()->update(['candidate_threshold' => 2]);

    // 2 candidates => Mode B
    $this->actingAs($admin)->post(route('admin.candidates.store'), ['name' => 'A'])->assertRedirect();
    $this->actingAs($admin)->post(route('admin.candidates.store'), ['name' => 'B'])->assertRedirect();
    expect(Election::current()->mode)->toBe(Election::MODE_AUTO);

    // 3rd candidate crosses the threshold => Mode A
    $this->actingAs($admin)->post(route('admin.candidates.store'), ['name' => 'C'])->assertRedirect();
    expect(Election::current()->mode)->toBe(Election::MODE_SELECT);
});
