<?php

namespace App\Imports;

use App\Models\Assembly;
use App\Models\AssemblyCompany;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports the eligible-companies list the admin prepares before the AG (CLAUDE.md rule 2).
 *
 * Accepts flexible French headers and common boolean spellings. Rows are upserted by
 * normalized name so re-importing a corrected file is safe. Invalid rows are collected
 * and reported back to the admin rather than silently dropped.
 */
class CompaniesImport implements ToCollection, WithHeadingRow
{
    /** Header aliases (normalized) → canonical field. */
    private const NAME_KEYS = ['nom', 'entreprise', 'societe', 'société', 'raison_sociale', 'membre', 'membres'];
    private const SURVEY_KEYS = ['enquete_2025', 'enquête_2025', 'enquete', 'survey_2025'];
    private const DUES_KEYS = ['cotisation_2025', 'cotisations_2025', 'cotisation', 'dues_2025'];
    private const NEW_MEMBER_KEYS = ['nouveau_membre_2026', 'nouveau_membre', 'new_member_2026'];

    public int $imported = 0;

    /** @var array<int, string> */
    public array $errors = [];

    private Assembly $assembly;

    public function __construct(?Assembly $assembly = null)
    {
        $this->assembly = $assembly ?? Assembly::current();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2; // +1 for heading row, +1 for 1-based display
            $name = $this->pick($row, self::NAME_KEYS);

            if ($name === null || trim($name) === '') {
                $this->errors[] = "Ligne {$line} : nom d’entreprise manquant — ignorée.";
                continue;
            }

            $company = Company::updateOrCreate(
                ['normalized_name' => Company::normalizeName($name)],
                [
                    'name' => trim($name),
                    'survey_2025' => $this->bool($this->pick($row, self::SURVEY_KEYS)),
                    'dues_2025' => $this->bool($this->pick($row, self::DUES_KEYS)),
                    'new_member_2026' => $this->bool($this->pick($row, self::NEW_MEMBER_KEYS)),
                ],
            );

            AssemblyCompany::updateOrCreate(
                [
                    'assembly_id' => $this->assembly->id,
                    'company_id' => $company->id,
                ],
                [
                    'name' => $company->name,
                    'normalized_name' => $company->normalized_name,
                    'survey_2025' => $company->survey_2025,
                    'dues_2025' => $company->dues_2025,
                    'new_member_2026' => $company->new_member_2026,
                    'eligible' => $company->isEligible(),
                ],
            );

            $this->imported++;
        }
    }

    /**
     * First non-null value among the given header aliases.
     */
    private function pick(Collection $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if ($row->has($key) && $row->get($key) !== null) {
                return (string) $row->get($key);
            }
        }

        return null;
    }

    /**
     * Parse common truthy spellings: oui, x, 1, vrai, true, yes.
     */
    private function bool(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(Str::lower(trim($value)), ['oui', 'x', '1', 'vrai', 'true', 'yes', 'o'], true);
    }
}
