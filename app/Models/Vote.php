<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A final, irrevocable ballot cast by one member company (CLAUDE.md rules 1 & 5).
 * The unique company_id (DB-enforced) guarantees one vote per company.
 */
class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'round',
        'is_proxy',
        'reference_number',
        'voted_at',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'is_proxy' => 'boolean',
            'voted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function selections(): HasMany
    {
        return $this->hasMany(VoteSelection::class);
    }

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(Candidate::class, 'vote_selections');
    }

    public function scopeRound(Builder $query, ?int $round): Builder
    {
        return $query->where('round', $round ?? 1);
    }
}
