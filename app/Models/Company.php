<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    /**
     * Eligible if up to date on the 2025 survey, OR 2025 dues, OR — for new
     * members — entry fees + 2026 dues (new_member_2026). CLAUDE.md rule 2.
     */
    public function isEligible(): bool
    {
        return $this->survey_2025 || $this->dues_2025 || $this->new_member_2026;
    }

    /**
     * Canonical form used to match imports and voter input regardless of casing/spacing.
     */
    public static function normalizeName(string $name): string
    {
        return Str::lower(Str::squish($name));
    }

    public function vote(): HasOne
    {
        return $this->hasOne(Vote::class);
    }

    public function hasVoted(): bool
    {
        return $this->vote()->exists();
    }
}
