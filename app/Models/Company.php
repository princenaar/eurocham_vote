<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A member company on the imported eligibility list (CLAUDE.md rules 1 & 2).
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'normalized_name',
        'survey_2025',
        'dues_2025',
        'new_member_2026',
    ];

    protected function casts(): array
    {
        return [
            'survey_2025' => 'boolean',
            'dues_2025' => 'boolean',
            'new_member_2026' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Company $company) {
            $assembly = Assembly::current();

            AssemblyCompany::firstOrCreate(
                ['assembly_id' => $assembly->id, 'company_id' => $company->id],
                [
                    'name' => $company->name,
                    'normalized_name' => $company->normalized_name,
                    'survey_2025' => (bool) ($company->survey_2025 ?? false),
                    'dues_2025' => (bool) ($company->dues_2025 ?? false),
                    'new_member_2026' => (bool) ($company->new_member_2026 ?? false),
                    'eligible' => $company->isEligible(),
                ],
            );
        });
    }

    /**
     * Eligible if up to date on both the 2025 survey and 2025 dues, OR — for
     * new members — entry fees + 2026 dues (new_member_2026).
     */
    public function isEligible(): bool
    {
        return ($this->survey_2025 && $this->dues_2025) || $this->new_member_2026;
    }

    public function scopeEligible(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $q) => $q
                ->where('survey_2025', true)
                ->where('dues_2025', true))
            ->orWhere('new_member_2026', true));
    }

    /**
     * Canonical form used to match imports and voter input regardless of casing/spacing.
     */
    public static function normalizeName(string $name): string
    {
        return Str::lower(Str::squish($name));
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function assemblyCompanies(): HasMany
    {
        return $this->hasMany(AssemblyCompany::class);
    }

    /**
     * Whether this company has already voted in the given round (one vote each per
     * round — rule 1). Defaults to the election's current round.
     */
    public function hasVoted(?Election $election = null, ?int $round = null): bool
    {
        $election ??= Election::active() ?? Election::current();
        $round ??= $election->current_round;

        return $this->votes()
            ->where('election_id', $election->id)
            ->where('round', $round)
            ->exists();
    }
}
