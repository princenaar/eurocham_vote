<?php

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\Vote;
use App\Models\VoteSelection;
use App\Support\ElectionResults;

/**
 * Results consolidation + boundary-tie detection (CLAUDE.md rule 8 + tiebreaker).
 * Correctness-critical: these figures decide who sits on the Board.
 */

/** Cast a round-`$round` ballot for `$company` choosing the given candidate ids. */
function castBallot(Company $company, array $candidateIds, int $round = 1): Vote
{
    $vote = Vote::create([
        'company_id' => $company->id,
        'round' => $round,
        'reference_number' => 'REF-'.$company->id.'-'.$round,
        'voted_at' => now(),
    ]);

    foreach ($candidateIds as $id) {
        VoteSelection::create(['vote_id' => $vote->id, 'candidate_id' => $id]);
    }

    return $vote;
}

function makeCompanies(int $n): array
{
    return collect(range(1, $n))->map(fn ($i) => Company::create([
        'name' => "Société {$i}",
        'normalized_name' => "societe {$i}",
        'survey_2025' => true,
        'dues_2025' => true,
    ]))->all();
}

it('ranks candidates by round-1 votes, descending', function () {
    $election = Election::current();
    $election->update(['candidate_threshold' => 2, 'mode' => Election::MODE_SELECT]);
    $a = Candidate::create(['name' => 'Alpha']);
    $b = Candidate::create(['name' => 'Bravo']);
    $c = Candidate::create(['name' => 'Charlie']);
    [$c1, $c2, $c3] = makeCompanies(3);

    castBallot($c1, [$a->id, $b->id]);
    castBallot($c2, [$a->id, $b->id]);
    castBallot($c3, [$a->id, $c->id]);

    $ranking = ElectionResults::for($election->fresh())->ranking(1);

    expect($ranking->pluck('votes')->all())->toBe([3, 2, 1]);
    expect($ranking->first()['candidate']->id)->toBe($a->id);
});

it('elects the top-N with no tie when votes are distinct', function () {
    $election = Election::current();
    $election->update(['candidate_threshold' => 2, 'mode' => Election::MODE_SELECT]);
    $a = Candidate::create(['name' => 'Alpha']);
    $b = Candidate::create(['name' => 'Bravo']);
    $c = Candidate::create(['name' => 'Charlie']);
    [$c1, $c2, $c3] = makeCompanies(3);

    castBallot($c1, [$a->id, $b->id]);
    castBallot($c2, [$a->id, $b->id]);
    castBallot($c3, [$a->id, $c->id]);

    $results = ElectionResults::for($election->fresh());

    expect($results->hasUnresolvedTie())->toBeFalse();
    expect($results->electedBoard()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$a->id, $b->id])->sort()->values()->all());
});

it('flags a boundary tie for the contested seat', function () {
    $election = Election::current();
    $election->update(['candidate_threshold' => 2, 'mode' => Election::MODE_SELECT]);
    $a = Candidate::create(['name' => 'Alpha']);
    $b = Candidate::create(['name' => 'Bravo']);
    $c = Candidate::create(['name' => 'Charlie']);
    [$c1, $c2] = makeCompanies(2);

    // Alpha=2, Bravo=1, Charlie=1 -> tie for the 2nd (last) seat between Bravo & Charlie.
    castBallot($c1, [$a->id, $b->id]);
    castBallot($c2, [$a->id, $c->id]);

    $results = ElectionResults::for($election->fresh());

    expect($results->hasUnresolvedTie())->toBeTrue();
    $tie = $results->pendingTie();
    expect($tie['seats'])->toBe(1);
    expect(collect($tie['tied'])->pluck('id')->sort()->values()->all())
        ->toBe(collect([$b->id, $c->id])->sort()->values()->all());

    // Only the clear winner is on the board until the tie is resolved.
    expect($results->electedBoard()->pluck('id')->all())->toBe([$a->id]);
});

it('resolves the board from a runoff round', function () {
    $election = Election::current();
    $election->update(['candidate_threshold' => 2, 'mode' => Election::MODE_SELECT]);
    $a = Candidate::create(['name' => 'Alpha']);
    $b = Candidate::create(['name' => 'Bravo']);
    $c = Candidate::create(['name' => 'Charlie']);
    [$c1, $c2] = makeCompanies(2);

    castBallot($c1, [$a->id, $b->id]);
    castBallot($c2, [$a->id, $c->id]);

    // Admin launches a runoff for the 1 contested seat between Bravo & Charlie.
    $election->update([
        'current_round' => 2,
        'runoff_candidate_ids' => [$b->id, $c->id],
        'runoff_seats' => 1,
    ]);

    // Round-2 vote: Bravo wins the runoff.
    castBallot($c1, [$b->id], round: 2);
    castBallot($c2, [$b->id], round: 2);

    $results = ElectionResults::for($election->fresh());

    expect($results->hasUnresolvedTie())->toBeFalse();
    expect($results->electedBoard()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$a->id, $b->id])->sort()->values()->all());
});

it('keeps partial winners from successive runoff rounds until the board is complete', function () {
    $election = Election::current();
    $election->update([
        'candidate_threshold' => 3,
        'candidate_min_choices' => 1,
        'candidate_max_choices' => 3,
        'mode' => Election::MODE_SELECT,
    ]);
    $alpha = Candidate::create(['name' => 'Alpha']);
    $bravo = Candidate::create(['name' => 'Bravo']);
    $charlie = Candidate::create(['name' => 'Charlie']);
    $delta = Candidate::create(['name' => 'Delta']);
    $echo = Candidate::create(['name' => 'Echo']);
    [$c1, $c2, $c3] = makeCompanies(3);

    // Main vote: Alpha is clear; four candidates are tied for the final two seats.
    castBallot($c1, [$alpha->id, $bravo->id, $charlie->id]);
    castBallot($c2, [$alpha->id, $delta->id, $echo->id]);

    // Round 2: Bravo wins one seat, Charlie/Delta/Echo remain tied for one seat.
    $election->update([
        'current_round' => 2,
        'runoff_candidate_ids' => [$bravo->id, $charlie->id, $delta->id, $echo->id],
        'runoff_seats' => 2,
    ]);
    $election->runoffRounds()->create([
        'round' => 2,
        'candidate_ids' => [$bravo->id, $charlie->id, $delta->id, $echo->id],
        'seats' => 2,
    ]);
    castBallot($c1, [$bravo->id, $charlie->id], round: 2);
    castBallot($c2, [$bravo->id, $delta->id], round: 2);
    castBallot($c3, [$bravo->id, $echo->id], round: 2);

    // Round 3 resolves the last remaining seat without erasing Bravo's round-2 win.
    $election->update([
        'current_round' => 3,
        'runoff_candidate_ids' => [$charlie->id, $delta->id, $echo->id],
        'runoff_seats' => 1,
    ]);
    $election->runoffRounds()->create([
        'round' => 3,
        'candidate_ids' => [$charlie->id, $delta->id, $echo->id],
        'seats' => 1,
    ]);
    castBallot($c1, [$charlie->id], round: 3);
    castBallot($c2, [$charlie->id], round: 3);
    castBallot($c3, [$delta->id], round: 3);

    $results = ElectionResults::for($election->fresh());

    expect($results->hasUnresolvedTie())->toBeFalse();
    expect($results->runoffRounds()->pluck('round')->all())->toBe([2, 3]);
    expect($results->electedBoard()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$alpha->id, $bravo->id, $charlie->id])->sort()->values()->all());
});

it('keeps the board incomplete when the latest runoff is still tied', function () {
    $election = Election::current();
    $election->update([
        'candidate_threshold' => 3,
        'candidate_min_choices' => 1,
        'candidate_max_choices' => 3,
        'mode' => Election::MODE_SELECT,
    ]);
    $alpha = Candidate::create(['name' => 'Alpha']);
    $bravo = Candidate::create(['name' => 'Bravo']);
    $charlie = Candidate::create(['name' => 'Charlie']);
    $delta = Candidate::create(['name' => 'Delta']);
    $echo = Candidate::create(['name' => 'Echo']);
    [$c1, $c2, $c3] = makeCompanies(3);

    castBallot($c1, [$alpha->id, $bravo->id, $charlie->id]);
    castBallot($c2, [$alpha->id, $delta->id, $echo->id]);

    $election->update([
        'current_round' => 2,
        'runoff_candidate_ids' => [$bravo->id, $charlie->id, $delta->id, $echo->id],
        'runoff_seats' => 2,
    ]);
    $election->runoffRounds()->create([
        'round' => 2,
        'candidate_ids' => [$bravo->id, $charlie->id, $delta->id, $echo->id],
        'seats' => 2,
    ]);
    castBallot($c1, [$bravo->id, $charlie->id], round: 2);
    castBallot($c2, [$bravo->id, $delta->id], round: 2);
    castBallot($c3, [$bravo->id, $echo->id], round: 2);

    $election->update([
        'current_round' => 3,
        'runoff_candidate_ids' => [$charlie->id, $delta->id, $echo->id],
        'runoff_seats' => 1,
    ]);
    $election->runoffRounds()->create([
        'round' => 3,
        'candidate_ids' => [$charlie->id, $delta->id, $echo->id],
        'seats' => 1,
    ]);
    castBallot($c1, [$charlie->id], round: 3);
    castBallot($c2, [$delta->id], round: 3);

    $results = ElectionResults::for($election->fresh());
    $tie = $results->pendingTie();

    expect($results->hasUnresolvedTie())->toBeTrue();
    expect($tie['seats'])->toBe(1);
    expect($tie['tied']->pluck('id')->sort()->values()->all())
        ->toBe(collect([$charlie->id, $delta->id])->sort()->values()->all());
    expect($results->electedBoard()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$alpha->id, $bravo->id])->sort()->values()->all());
});

it('treats every candidate as elected in Mode B', function () {
    $election = Election::current();
    $election->update(['candidate_threshold' => 20, 'mode' => Election::MODE_AUTO]);
    $a = Candidate::create(['name' => 'Alpha', 'auto_elected' => true]);
    $b = Candidate::create(['name' => 'Bravo', 'auto_elected' => true]);

    $results = ElectionResults::for($election->fresh());

    expect($results->hasUnresolvedTie())->toBeFalse();
    expect($results->electedBoard()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$a->id, $b->id])->sort()->values()->all());
});
