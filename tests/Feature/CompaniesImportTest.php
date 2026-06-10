<?php

use App\Imports\CompaniesImport;
use App\Models\Company;

/**
 * Eligible-company import (CLAUDE.md rule 2): flexible headers, eligibility flags,
 * upsert by normalized name, and an error report for invalid rows.
 */

function importRows(array $rows): CompaniesImport
{
    $import = new CompaniesImport();
    $import->collection(collect($rows)->map(fn ($r) => collect($r)));

    return $import;
}

it('imports companies and computes eligibility from dues plus survey, or new member status', function () {
    importRows([
        ['nom' => 'ACME SA', 'cotisation_2025' => 'oui', 'enquete_2025' => 'oui'],
        ['nom' => 'Beta SARL', 'enquete_2025' => 'X'],
        ['nom' => 'Gamma', 'nouveau_membre_2026' => '1'],
        ['nom' => 'Delta', 'cotisation_2025' => 'oui'],
    ]);

    expect(Company::count())->toBe(4);
    expect(Company::where('name', 'ACME SA')->first()->isEligible())->toBeTrue();
    expect(Company::where('name', 'Beta SARL')->first()->isEligible())->toBeFalse();
    expect(Company::where('name', 'Gamma')->first()->isEligible())->toBeTrue();
    expect(Company::where('name', 'Delta')->first()->isEligible())->toBeFalse();
});

it('upserts by normalized name on re-import', function () {
    importRows([['nom' => 'ACME SA', 'cotisation_2025' => 'non', 'enquete_2025' => 'oui']]);
    importRows([['nom' => '  acme   sa ', 'cotisation_2025' => 'oui', 'enquete_2025' => 'oui']]);

    expect(Company::count())->toBe(1);
    expect(Company::first()->dues_2025)->toBeTrue();
    expect(Company::first()->isEligible())->toBeTrue();
});

it('accepts membres as a company-name header alias', function () {
    importRows([
        ['membres' => '2S CONSULTING', 'cotisation_2025' => 'oui', 'enquete_2025' => 'oui'],
    ]);

    expect(Company::count())->toBe(1);
    expect(Company::first()->name)->toBe('2S CONSULTING');
    expect(Company::first()->isEligible())->toBeTrue();
});

it('skips rows without a company name and reports them', function () {
    $import = importRows([
        ['nom' => 'ACME SA', 'cotisation_2025' => 'oui'],
        ['cotisation_2025' => 'oui'], // no name
    ]);

    expect($import->imported)->toBe(1);
    expect($import->errors)->toHaveCount(1);
    expect(Company::count())->toBe(1);
});
