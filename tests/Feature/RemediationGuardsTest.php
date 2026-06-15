<?php

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

function remediationAdmin(): User
{
    return User::factory()->create();
}

function remediationReadyElection(): Election
{
    $election = Election::current();
    $election->update([
        'candidate_threshold' => 2,
        'candidate_min_choices' => 2,
        'candidate_max_choices' => 2,
        'qr_active' => true,
    ]);

    Candidate::create(['name' => 'Alpha']);
    Candidate::create(['name' => 'Bravo']);
    Candidate::create(['name' => 'Charlie']);

    Company::create([
        'name' => 'Entreprise Eligible',
        'normalized_name' => Company::normalizeName('Entreprise Eligible'),
        'survey_2025' => true,
        'dues_2025' => true,
    ]);

    $election->syncModeFromCandidates();

    return $election->fresh();
}

it('blocks candidate edits once the election is open', function () {
    $admin = remediationAdmin();
    $election = remediationReadyElection();
    $candidate = Candidate::first();

    $election->update(['status' => Election::STATUS_OPEN, 'window_open' => true]);

    $this->actingAs($admin)
        ->put(route('admin.candidates.update', $candidate), ['name' => 'Modifié'])
        ->assertSessionHasErrors('candidates');

    expect($candidate->fresh()->name)->toBe('Alpha');
});

it('blocks member imports once the election is closed', function () {
    $admin = remediationAdmin();
    remediationReadyElection()->update(['status' => Election::STATUS_CLOSED, 'window_open' => false, 'closed_at' => now()]);

    $this->actingAs($admin)
        ->post(route('admin.companies.import.store'), [])
        ->assertSessionHasErrors('file');
});

it('refuses to open the main vote when the configuration is incomplete', function () {
    $admin = remediationAdmin();
    Election::current()->update(['status' => Election::STATUS_READY, 'qr_active' => false]);

    $this->actingAs($admin)
        ->post(route('admin.election.window'))
        ->assertSessionHasErrors('window');

    expect(Election::current()->window_open)->toBeFalse();
});

it('opens and closes the main vote through explicit lifecycle states', function () {
    $admin = remediationAdmin();
    remediationReadyElection();

    $this->actingAs($admin)->post(route('admin.election.window'))->assertRedirect();
    expect(Election::current()->status)->toBe(Election::STATUS_OPEN);
    expect(Election::current()->window_open)->toBeTrue();

    $this->actingAs($admin)->post(route('admin.election.window'))->assertRedirect();
    expect(Election::current()->status)->toBe(Election::STATUS_CLOSED);
    expect(Election::current()->window_open)->toBeFalse();
});

it('blocks final exports before the election is closed', function () {
    $admin = remediationAdmin();
    remediationReadyElection()->update(['status' => Election::STATUS_OPEN, 'window_open' => true]);

    $this->actingAs($admin)->get(route('admin.results.excel'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.results.pdf'))->assertForbidden();
});

it('records a proxy vote flag without creating an extra ballot row', function () {
    $election = remediationReadyElection();
    $election->update(['status' => Election::STATUS_OPEN, 'window_open' => true, 'qr_active' => true]);
    $company = Company::first();
    $chosen = Candidate::query()->take(2)->pluck('id')->all();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
        'is_proxy' => '1',
    ])->assertRedirect(route('vote.ballot'));

    $this->get(route('vote.ballot'))
        ->assertOk()
        ->assertSee('Vote par procuration');

    $this->post(route('vote.submit'), ['candidates' => $chosen])
        ->assertRedirect(route('vote.confirmation'));

    expect(Vote::count())->toBe(1);
    expect(Vote::first()->is_proxy)->toBeTrue();
});

it('rate limits repeated admin login failures', function () {
    User::factory()->create(['email' => 'admin@eurocham.sn', 'password' => bcrypt('correct')]);
    RateLimiter::clear('admin@eurocham.sn|127.0.0.1');

    foreach (range(1, 5) as $i) {
        $this->post(route('admin.login.attempt'), [
            'email' => 'admin@eurocham.sn',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');
    }

    $this->post(route('admin.login.attempt'), [
        'email' => 'admin@eurocham.sn',
        'password' => 'wrong',
    ])->assertSessionHasErrors('email');

    expect(RateLimiter::tooManyAttempts('admin@eurocham.sn|127.0.0.1', 5))->toBeTrue();
});

it('rejects invalid production configuration', function () {
    $this->app->detectEnvironment(fn () => 'production');
    config([
        'database.default' => 'sqlite',
        'cache.default' => 'array',
        'cache.vote_lock_store' => 'array',
        'session.driver' => 'array',
        'session.secure' => false,
        'app.debug' => true,
    ]);

    expect(fn () => (new AppServiceProvider($this->app))->boot())
        ->toThrow(RuntimeException::class);
});

it('can use the configured vote lock store', function () {
    config(['cache.vote_lock_store' => 'array']);

    $lock = Cache::store(config('cache.vote_lock_store'))->lock('vote:test-lock', 1);

    expect($lock->get())->toBeTrue();
    $lock->release();
});
