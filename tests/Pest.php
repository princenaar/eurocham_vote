<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Company;
use App\Models\Vote;
use App\Models\VoteSelection;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

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

function eligibleCompany(string $name = 'ACME SARL'): Company
{
    return Company::create([
        'name' => $name,
        'normalized_name' => Company::normalizeName($name),
        'survey_2025' => true,
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

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
