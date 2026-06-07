<?php

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;

/**
 * Phase 4 — tiebreaker runoff launch + restricted voting, and public results gating
 * (CLAUDE.md rules 7 & 8). Helpers castBallot/makeCompanies/eligibleCompany are shared
 * from the other Feature test files.
 */

/**
 * Mode A scrutin with Alpha=2, Bravo=1, Charlie=1 → tie for the 2nd (last) seat,
 * window closed, ready for a runoff. Returns [election, [alpha, bravo, charlie]].
 */
function tiedModeAElection(): array
{
    $election = Election::current();
    $election->update([
        'candidate_threshold' => 2,
        'mode' => Election::MODE_SELECT,
        'window_open' => false,
        'qr_active' => true,
        'closed_at' => now(),
    ]);

    $alpha = Candidate::create(['name' => 'Alpha', 'display_order' => 1]);
    $bravo = Candidate::create(['name' => 'Bravo', 'display_order' => 2]);
    $charlie = Candidate::create(['name' => 'Charlie', 'display_order' => 3]);

    [$c1, $c2] = makeCompanies(2);
    castBallot($c1, [$alpha->id, $bravo->id]);
    castBallot($c2, [$alpha->id, $charlie->id]);

    return [$election->fresh(), [$alpha, $bravo, $charlie]];
}

it('launches a runoff for the contested seat when the window is closed', function () {
    [$election, [$alpha, $bravo, $charlie]] = tiedModeAElection();
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.election.runoff'))
        ->assertRedirect(route('admin.election.edit'));

    $election->refresh();
    expect($election->current_round)->toBe(2);
    expect($election->runoff_seats)->toBe(1);
    expect($election->runoff_candidate_ids)->toMatchArray([$bravo->id, $charlie->id]);
    expect($election->window_open)->toBeTrue();
});

it('refuses to launch a runoff while the window is still open', function () {
    [$election] = tiedModeAElection();
    $election->update(['window_open' => true]);
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.election.runoff'))
        ->assertSessionHasErrors('runoff');

    expect($election->fresh()->current_round)->toBe(1);
});

it('refuses to launch a runoff when there is no tie', function () {
    $election = Election::current();
    $election->update(['candidate_threshold' => 2, 'mode' => Election::MODE_SELECT, 'window_open' => false, 'closed_at' => now()]);
    $alpha = Candidate::create(['name' => 'Alpha']);
    $bravo = Candidate::create(['name' => 'Bravo']);
    $charlie = Candidate::create(['name' => 'Charlie']);
    [$c1] = makeCompanies(1);
    castBallot($c1, [$alpha->id, $bravo->id]); // Alpha=1, Bravo=1, Charlie=0 → top 2 clear.
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.election.runoff'))
        ->assertSessionHasErrors('runoff');

    expect($election->fresh()->current_round)->toBe(1);
});

it('restricts the runoff ballot to the tied candidates and the remaining seats', function () {
    [$election, [$alpha, $bravo, $charlie]] = tiedModeAElection();
    $admin = User::factory()->create();
    $this->actingAs($admin)->post(route('admin.election.runoff'));

    $voter = eligibleCompany('Votant Runoff');

    $this->post(route('vote.identify'), [
        'company_id' => $voter->id,
        'last_name' => 'Sow',
        'first_name' => 'Modou',
    ])->assertRedirect(route('vote.ballot'));

    // A non-tied candidate (Alpha) cannot be chosen in the runoff.
    $this->post(route('vote.submit'), ['candidates' => [$alpha->id]])
        ->assertSessionHasErrors('candidates.0');
    expect(Vote::query()->where('round', 2)->count())->toBe(0);

    // Exactly the remaining seat from the tied set is accepted, recorded in round 2.
    $this->post(route('vote.submit'), ['candidates' => [$bravo->id]])
        ->assertRedirect(route('vote.confirmation'));
    expect(Vote::query()->where('round', 2)->where('company_id', $voter->id)->count())->toBe(1);
});

it('lets a company that voted in round 1 vote again in the runoff', function () {
    [$election, [$alpha, $bravo, $charlie]] = tiedModeAElection();
    // Reuse one of the round-1 companies as the runoff voter.
    $c1 = Company::where('name', 'Société 1')->first();

    $admin = User::factory()->create();
    $this->actingAs($admin)->post(route('admin.election.runoff'));

    $this->post(route('vote.identify'), [
        'company_id' => $c1->id,
        'last_name' => 'Ba',
        'first_name' => 'Awa',
    ])->assertRedirect(route('vote.ballot'));

    $this->post(route('vote.submit'), ['candidates' => [$charlie->id]])
        ->assertRedirect(route('vote.confirmation'));

    expect(Vote::query()->where('company_id', $c1->id)->count())->toBe(2);
});

it('hides public results while voting is open, showing turnout only', function () {
    $election = Election::current();
    $election->update([
        'candidate_threshold' => 2, 'mode' => Election::MODE_SELECT,
        'window_open' => true, 'qr_active' => true,
    ]);
    Candidate::create(['name' => 'Alpha']);

    $this->get(route('results.public'))
        ->assertOk()
        ->assertViewIs('vote.results-pending');
});

it('reveals public results automatically once the window is closed', function () {
    [$election, [$alpha]] = tiedModeAElection();

    $this->get(route('results.public'))
        ->assertOk()
        ->assertViewIs('vote.results')
        ->assertSee('Alpha');
});

it('renders the admin results page and exports with the round-aware data', function () {
    [$election, [$alpha, $bravo, $charlie]] = tiedModeAElection();
    $admin = User::factory()->create();

    $this->actingAs($admin)->get(route('admin.results.index'))
        ->assertOk()
        ->assertSee('Alpha')
        ->assertSee('Lancer le départage'); // tie banner present

    $this->actingAs($admin)->get(route('admin.results.excel'))->assertOk();
    $this->actingAs($admin)->get(route('admin.results.pdf'))->assertOk();
});

it('shows the auto-elected board publicly in Mode B after close', function () {
    $election = Election::current();
    $election->update([
        'candidate_threshold' => 20, 'mode' => Election::MODE_AUTO,
        'window_open' => false, 'closed_at' => now(),
    ]);
    Candidate::create(['name' => 'Alpha', 'auto_elected' => true]);
    Candidate::create(['name' => 'Bravo', 'auto_elected' => true]);

    $this->get(route('results.public'))
        ->assertOk()
        ->assertViewIs('vote.results')
        ->assertSee('Alpha')
        ->assertSee('Bravo');
});
