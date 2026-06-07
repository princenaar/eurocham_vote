<?php

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\Vote;

/**
 * QR-gated public voter flow — server-side enforcement of every electoral rule
 * (CLAUDE.md rules 1–6). These are correctness-critical for a real election.
 */

/**
 * Configure a live Mode A scrutin: open window, threshold seats, and
 * (threshold + extra) candidates so the voter must pick exactly `threshold`.
 */
function modeAElection(int $threshold = 3, int $candidates = 5): Election
{
    $election = Election::current();
    $election->update([
        'candidate_threshold' => $threshold,
        'window_open' => true,
        'qr_active' => true,
    ]);

    foreach (range(1, $candidates) as $i) {
        Candidate::create(['name' => "Candidat {$i}", 'display_order' => $i]);
    }
    $election->syncModeFromCandidates();

    return $election->fresh();
}

function eligibleCompany(string $name = 'ACME SARL'): Company
{
    return Company::create([
        'name' => $name,
        'normalized_name' => Company::normalizeName($name),
        'dues_2025' => true,
    ]);
}

function ineligibleCompany(string $name = 'INELIGIBLE SA'): Company
{
    return Company::create([
        'name' => $name,
        'normalized_name' => Company::normalizeName($name),
        'survey_2025' => false,
        'dues_2025' => false,
        'new_member_2026' => false,
    ]);
}

it('shows the closed message when the voting window is shut', function () {
    modeAElection();
    Election::current()->update(['window_open' => false]);

    $this->get(route('vote.start'))->assertOk()->assertViewIs('vote.closed');
});

it('rejects identification when the window is closed', function () {
    modeAElection();
    Election::current()->update(['window_open' => false]);
    $company = eligibleCompany();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ])->assertSessionHasErrors();

    expect(Vote::count())->toBe(0);
});

it('blocks an ineligible company with a contact-secretariat message', function () {
    modeAElection();
    $company = ineligibleCompany();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ])->assertSessionHasErrors('company_id');
});

it('lets an eligible company through to the Mode A ballot', function () {
    modeAElection();
    $company = eligibleCompany();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ])->assertRedirect(route('vote.ballot'));

    $this->get(route('vote.ballot'))->assertOk()->assertViewIs('vote.ballot');
});

it('records a vote with exactly the required number of selections', function () {
    $election = modeAElection(threshold: 3, candidates: 5);
    $company = eligibleCompany();
    $chosen = Candidate::query()->take(3)->pluck('id')->all();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ]);

    $this->post(route('vote.submit'), ['candidates' => $chosen])
        ->assertRedirect(route('vote.confirmation'));

    $vote = Vote::query()->where('company_id', $company->id)->first();
    expect($vote)->not->toBeNull();
    expect($vote->reference_number)->not->toBeEmpty();
    expect($vote->selections()->count())->toBe(3);

    // The confirmation screen renders and shows the reference number (rule 5).
    $this->get(route('vote.confirmation'))
        ->assertOk()
        ->assertViewIs('vote.confirmation')
        ->assertSee($vote->reference_number);
});

it('renders the review screen with the chosen candidates', function () {
    modeAElection(threshold: 3, candidates: 5);
    $company = eligibleCompany();
    $chosen = Candidate::query()->take(3)->pluck('id')->all();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ]);

    $this->post(route('vote.review'), ['candidates' => $chosen])
        ->assertOk()
        ->assertViewIs('vote.review')
        ->assertSee('Candidat 1');
});

it('rejects a Mode A ballot with fewer than the required selections', function () {
    modeAElection(threshold: 3, candidates: 5);
    $company = eligibleCompany();
    $chosen = Candidate::query()->take(2)->pluck('id')->all();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ]);

    $this->post(route('vote.submit'), ['candidates' => $chosen])
        ->assertSessionHasErrors('candidates');

    expect(Vote::count())->toBe(0);
});

it('rejects a Mode A ballot with more than the required selections', function () {
    modeAElection(threshold: 3, candidates: 5);
    $company = eligibleCompany();
    $chosen = Candidate::query()->take(4)->pluck('id')->all();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ]);

    $this->post(route('vote.submit'), ['candidates' => $chosen])
        ->assertSessionHasErrors('candidates');

    expect(Vote::count())->toBe(0);
});

it('blocks a second vote by the same company', function () {
    modeAElection(threshold: 3, candidates: 5);
    $company = eligibleCompany();
    $chosen = Candidate::query()->take(3)->pluck('id')->all();

    // First, successful vote.
    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ]);
    $this->post(route('vote.submit'), ['candidates' => $chosen])
        ->assertRedirect(route('vote.confirmation'));

    // Second attempt — re-identify must be refused.
    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ])->assertSessionHasErrors('company_id');

    expect(Vote::query()->where('company_id', $company->id)->count())->toBe(1);
});

it('short-circuits to the auto-election result in Mode B without a ballot', function () {
    $election = Election::current();
    $election->update([
        'candidate_threshold' => 20,
        'window_open' => true,
        'qr_active' => true,
    ]);
    foreach (range(1, 5) as $i) {
        Candidate::create(['name' => "Candidat {$i}", 'display_order' => $i]);
    }
    $election->syncModeFromCandidates();
    expect($election->fresh()->mode)->toBe(Election::MODE_AUTO);

    $this->get(route('vote.start'))->assertOk()->assertViewIs('vote.auto');

    // No ballot is offered in Mode B.
    $this->get(route('vote.ballot'))->assertRedirect(route('vote.start'));
    expect(Vote::count())->toBe(0);
});

it('refuses to submit when the window closes mid-session', function () {
    modeAElection(threshold: 3, candidates: 5);
    $company = eligibleCompany();
    $chosen = Candidate::query()->take(3)->pluck('id')->all();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ]);

    Election::current()->update(['window_open' => false]);

    // A closed window aborts the flow back to the (now-closed) landing page; no vote is cast.
    $this->post(route('vote.submit'), ['candidates' => $chosen])
        ->assertRedirect(route('vote.start'));

    expect(Vote::count())->toBe(0);
});
