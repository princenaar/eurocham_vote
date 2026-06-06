<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A candidate for the 2026 Board. Count drives the scrutin mode (CLAUDE.md rule 4).
 */
class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_order',
        'auto_elected',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'auto_elected' => 'boolean',
        ];
    }

    public function selections(): HasMany
    {
        return $this->hasMany(VoteSelection::class);
    }
}
