<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable definition of one tiebreaker round for a board election.
 */
class ElectionRunoffRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'round',
        'candidate_ids',
        'seats',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'candidate_ids' => 'array',
            'seats' => 'integer',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }
}
