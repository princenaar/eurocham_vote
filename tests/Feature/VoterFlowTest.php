<?php

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Vote;
use Illuminate\Support\Facades\Cache;

/**
 * QR-gated public voter flow — server-side enforcement of every electoral rule
 * (CLAUDE.md rules 1–6). These are correctness-critical for a real election.
 */

/**
 * Configure a live Mode A scrutin: open window, threshold seats, and
 * enough candidates to cross the Mode A threshold. By default the helper keeps
 * legacy exact-count semantics for compact tests; range tests pass min/max.
 */
function modeAElection(
    int $threshold = 3,
    int $candidates = 5,
    ?int $minChoices = null,
    ?int $maxChoices = null,
): Election
{
    $minChoices ??= $threshold;
    $maxChoices ??= $threshold;

    $election = Election::current();
    $election->update([
        'candidate_threshold' => $threshold,
        'candidate_min_choices' => $minChoices,
        'candidate_max_choices' => $maxChoices,
        'status' => Election::STATUS_OPEN,
        'window_open' => true,
        'qr_active' => true,
    ]);

    foreach (range(1, $candidates) as $i) {
        Candidate::create(['name' => "Candidat {$i}", 'display_order' => $i]);
    }
    $election->syncModeFromCandidates();

    return $election->fresh();
}

it('shows the closed message when the voting window is shut', function () {
    modeAElection();
    Election::current()->update(['status' => Election::STATUS_CLOSED, 'window_open' => false, 'closed_at' => now()]);

    $this->get(route('vote.start'))->assertOk()->assertViewIs('vote.closed');
});

it('rejects identification when the window is closed', function () {
    modeAElection();
    Election::current()->update(['status' => Election::STATUS_CLOSED, 'window_open' => false, 'closed_at' => now()]);
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

    $this->get(route('vote.ballot'))
        ->assertOk()
        ->assertViewIs('vote.ballot')
        ->assertSee('ordre d’inscription');
});

it('records a CA vote with the minimum allowed number of selections', function () {
    modeAElection(threshold: 20, candidates: 21, minChoices: 5, maxChoices: 20);
    $company = eligibleCompany();
    $chosen = Candidate::query()->take(5)->pluck('id')->all();

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
    expect($vote->is_proxy)->toBeFalse();
    expect($vote->selections()->count())->toBe(5);

    // The confirmation screen renders and shows the reference number (rule 5).
    $this->get(route('vote.confirmation'))
        ->assertOk()
        ->assertViewIs('vote.confirmation')
        ->assertSee($vote->reference_number);
});

it('records a CA vote with the maximum allowed number of selections', function () {
    modeAElection(threshold: 20, candidates: 21, minChoices: 5, maxChoices: 20);
    $company = eligibleCompany();
    $chosen = Candidate::query()->take(20)->pluck('id')->all();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ]);

    $this->post(route('vote.submit'), ['candidates' => $chosen])
        ->assertRedirect(route('vote.confirmation'));

    $vote = Vote::query()->where('company_id', $company->id)->first();
    expect($vote)->not->toBeNull();
    expect($vote->selections()->count())->toBe(20);
});

it('records a proxy vote for the selected represented company', function () {
    modeAElection(threshold: 3, candidates: 5);
    $represented = eligibleCompany('Entreprise Représentée');
    $chosen = Candidate::query()->take(3)->pluck('id')->all();

    $this->post(route('vote.identify'), [
        'company_id' => $represented->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
        'is_proxy' => '1',
    ])->assertRedirect(route('vote.ballot'));

    $this->get(route('vote.ballot'))
        ->assertOk()
        ->assertSee('Vote par procuration');

    $this->post(route('vote.review'), ['candidates' => $chosen])
        ->assertOk()
        ->assertSee('Vote par procuration');

    $this->post(route('vote.submit'), ['candidates' => $chosen])
        ->assertRedirect(route('vote.confirmation'));

    $vote = Vote::query()->where('company_id', $represented->id)->first();
    expect($vote)->not->toBeNull();
    expect($vote->is_proxy)->toBeTrue();

    $this->post(route('vote.identify'), [
        'company_id' => $represented->id,
        'last_name' => 'Sarr',
        'first_name' => 'Moussa',
    ])->assertSessionHasErrors('company_id');
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

it('rejects a CA ballot below the minimum selection count', function () {
    modeAElection(threshold: 20, candidates: 21, minChoices: 5, maxChoices: 20);
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

it('rejects a CA ballot above the maximum selection count', function () {
    modeAElection(threshold: 20, candidates: 21, minChoices: 5, maxChoices: 20);
    $company = eligibleCompany();
    $chosen = Candidate::query()->take(21)->pluck('id')->all();

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
        'candidate_min_choices' => 5,
        'candidate_max_choices' => 20,
        'status' => Election::STATUS_OPEN,
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

it('serialises submission per company: a held lock blocks a concurrent submit', function () {
    modeAElection(threshold: 3, candidates: 5);
    $company = eligibleCompany();
    $chosen = Candidate::query()->take(3)->pluck('id')->all();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ]);

    // Simulate a concurrent, in-flight submission already holding the per-company lock.
    $lock = Cache::lock("vote:company:{$company->id}:round:1", 10);
    expect($lock->get())->toBeTrue();

    // The submit can't acquire the lock within its short window → block-and-retry message,
    // and crucially no vote is written.
    $this->post(route('vote.submit'), ['candidates' => $chosen])
        ->assertSessionHasErrors('candidates');

    expect(Vote::count())->toBe(0);

    $lock->release();
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

    Election::current()->update(['status' => Election::STATUS_CLOSED, 'window_open' => false, 'closed_at' => now()]);

    // A closed window aborts the flow back to the (now-closed) landing page; no vote is cast.
    $this->post(route('vote.submit'), ['candidates' => $chosen])
        ->assertRedirect(route('vote.start'));

    expect(Vote::count())->toBe(0);
});
