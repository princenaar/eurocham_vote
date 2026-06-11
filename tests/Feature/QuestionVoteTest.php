<?php

use App\Models\Assembly;
use App\Models\Company;
use App\Models\Election;
use App\Models\ElectionQuestion;
use App\Models\QuestionResponse;
use App\Models\User;
use App\Models\Vote;
use App\Support\ElectionResults;

function questionVoteFixture(string $name = 'Résolutions ordinaires'): array
{
    $assembly = Assembly::current();

    $company = Company::create([
        'name' => 'Entreprise Question',
        'normalized_name' => Company::normalizeName('Entreprise Question'),
        'survey_2025' => true,
        'dues_2025' => true,
    ]);

    $election = $assembly->elections()->create([
        'name' => $name,
        'type' => Election::TYPE_QUESTIONS,
        'status' => Election::STATUS_OPEN,
        'window_open' => true,
        'qr_active' => true,
        'active_slot' => Election::ACTIVE_SLOT_GLOBAL,
        'display_order' => 2,
    ]);

    $q1 = $election->questions()->create([
        'title' => 'Approuver le rapport moral',
        'description' => 'Rapport présenté en séance.',
        'display_order' => 1,
    ]);
    $q2 = $election->questions()->create([
        'title' => 'Approuver les comptes',
        'description' => 'Comptes annuels.',
        'display_order' => 2,
    ]);

    return [$assembly, $election, $company, $q1, $q2];
}

it('records a grouped Oui Non Abstention ballot with one reference', function () {
    [$assembly, $election, $company, $q1, $q2] = questionVoteFixture();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
        'is_proxy' => '1',
    ])->assertRedirect(route('vote.ballot'));

    $this->get(route('vote.ballot'))
        ->assertOk()
        ->assertViewIs('vote.questions-ballot')
        ->assertSee('Approuver le rapport moral');

    $answers = [
        $q1->id => 'yes',
        $q2->id => 'abstain',
    ];

    $this->post(route('vote.review'), ['answers' => $answers])
        ->assertOk()
        ->assertViewIs('vote.questions-review')
        ->assertSee('Abstention');

    $this->post(route('vote.submit'), ['answers' => $answers])
        ->assertRedirect(route('vote.confirmation'));

    $vote = Vote::query()->where('election_id', $election->id)->where('company_id', $company->id)->first();
    expect($vote)->not->toBeNull();
    expect($vote->is_proxy)->toBeTrue();
    expect($vote->responses()->count())->toBe(2);
    expect($vote->responses()->where('election_question_id', $q1->id)->first()->answer)->toBeTrue();
    expect($vote->responses()->where('election_question_id', $q2->id)->first()->answer)->toBeNull();
});

it('allows the same company to vote once in each vote of the AG', function () {
    [$assembly, $firstVote, $company, $q1, $q2] = questionVoteFixture();

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ]);
    $this->post(route('vote.submit'), ['answers' => [$q1->id => 'yes', $q2->id => 'no']])
        ->assertRedirect(route('vote.confirmation'));

    $firstVote->update([
        'status' => Election::STATUS_CLOSED,
        'window_open' => false,
        'active_slot' => null,
        'closed_at' => now(),
    ]);

    $secondVote = $assembly->elections()->create([
        'name' => 'Vote complémentaire',
        'type' => Election::TYPE_QUESTIONS,
        'status' => Election::STATUS_OPEN,
        'window_open' => true,
        'qr_active' => true,
        'active_slot' => Election::ACTIVE_SLOT_GLOBAL,
        'display_order' => 3,
    ]);
    $q3 = $secondVote->questions()->create(['title' => 'Nouvelle résolution', 'display_order' => 1]);

    $this->post(route('vote.identify'), [
        'company_id' => $company->id,
        'last_name' => 'Diop',
        'first_name' => 'Awa',
    ])->assertRedirect(route('vote.ballot'));

    $this->post(route('vote.submit'), ['answers' => [$q3->id => 'abstain']])
        ->assertRedirect(route('vote.confirmation'));

    expect(Vote::query()->where('company_id', $company->id)->count())->toBe(2);
    expect(Vote::query()->where('election_id', $firstVote->id)->count())->toBe(1);
    expect(Vote::query()->where('election_id', $secondVote->id)->count())->toBe(1);
});

it('prevents opening two votes globally at the same time', function () {
    [$assembly, $firstVote] = questionVoteFixture();
    $firstVote->update(['status' => Election::STATUS_READY, 'window_open' => false, 'active_slot' => null]);

    $secondVote = $assembly->elections()->create([
        'name' => 'Second vote',
        'type' => Election::TYPE_QUESTIONS,
        'status' => Election::STATUS_READY,
        'qr_active' => true,
        'display_order' => 3,
    ]);
    $secondVote->questions()->create(['title' => 'Question concurrente', 'display_order' => 1]);

    $admin = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.election.window'), ['election_id' => $firstVote->id])
        ->assertRedirect();
    expect($firstVote->fresh()->isVotingOpen())->toBeTrue();

    $this->actingAs($admin)->post(route('admin.election.window'), ['election_id' => $secondVote->id])
        ->assertSessionHasErrors('window');
    expect($secondVote->fresh()->window_open)->toBeFalse();
});

it('calculates question results without letting abstention win', function () {
    [$assembly, $election, $company, $q1, $q2] = questionVoteFixture();
    $companies = collect([$company]);
    foreach (['Entreprise Deux', 'Entreprise Trois'] as $name) {
        $companies->push(Company::create([
            'name' => $name,
            'normalized_name' => Company::normalizeName($name),
            'survey_2025' => true,
            'dues_2025' => true,
        ]));
    }

    $answers = [
        [$q1->id => true, $q2->id => true],
        [$q1->id => null, $q2->id => false],
        [$q1->id => null, $q2->id => null],
    ];

    foreach ($companies as $index => $votingCompany) {
        $vote = Vote::create([
            'election_id' => $election->id,
            'company_id' => $votingCompany->id,
            'reference_number' => 'QREF-'.$index,
            'voted_at' => now(),
        ]);

        foreach ($answers[$index] as $questionId => $answer) {
            QuestionResponse::create([
                'vote_id' => $vote->id,
                'election_question_id' => $questionId,
                'answer' => $answer,
            ]);
        }
    }

    $results = ElectionResults::for($election)->questionResults()->keyBy(fn ($row) => $row['question']->id);

    expect($results[$q1->id]['yes'])->toBe(1);
    expect($results[$q1->id]['abstain'])->toBe(2);
    expect($results[$q1->id]['result'])->toBe('Oui');
    expect($results[$q1->id]['yes_percent'])->toBe(100.0);

    expect($results[$q2->id]['yes'])->toBe(1);
    expect($results[$q2->id]['no'])->toBe(1);
    expect($results[$q2->id]['result'])->toBe('Égalité');
});
