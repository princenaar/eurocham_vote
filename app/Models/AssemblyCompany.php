<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssemblyCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'assembly_id',
        'company_id',
        'name',
        'normalized_name',
        'survey_2025',
        'dues_2025',
        'new_member_2026',
        'eligible',
    ];

    protected function casts(): array
    {
        return [
            'survey_2025' => 'boolean',
            'dues_2025' => 'boolean',
            'new_member_2026' => 'boolean',
            'eligible' => 'boolean',
        ];
    }

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function scopeEligible(Builder $query): Builder
    {
        return $query->where('eligible', true);
    }

    public function hasVoted(Election $election, ?int $round = null): bool
    {
        $round ??= $election->current_round;

        return $this->votes()
            ->where('election_id', $election->id)
            ->where('round', $round)
            ->exists();
    }
}
