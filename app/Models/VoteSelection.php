<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One candidate chosen on a Mode A ballot (CLAUDE.md rule 4).
 */
class VoteSelection extends Model
{
    use HasFactory;

    protected $fillable = [
        'vote_id',
        'candidate_id',
    ];

    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
