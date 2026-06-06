<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The single configurable scrutin gating the voter flow (CLAUDE.md rules 4 & 6).
 */
class Election extends Model
{
    use HasFactory;

    public const MODE_SELECT = 'A';   // > threshold candidates: voter picks exactly <threshold>.
    public const MODE_AUTO = 'B';     // <= threshold candidates: all auto-elected, no ballot.

    protected $fillable = [
        'name',
        'mode',
        'candidate_threshold',
        'window_open',
        'qr_active',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'candidate_threshold' => 'integer',
            'window_open' => 'boolean',
            'qr_active' => 'boolean',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Resolve the scrutin mode from a candidate count (rule 4).
     * > threshold => Mode A (select exactly threshold); <= threshold => Mode B (auto-elect).
     */
    public function resolveMode(int $candidateCount): string
    {
        return $candidateCount > $this->candidate_threshold
            ? self::MODE_SELECT
            : self::MODE_AUTO;
    }

    /**
     * Voting is only accepted while the window is open AND the QR gate is active (rule 6).
     */
    public function isVotingOpen(): bool
    {
        return $this->window_open && $this->qr_active;
    }
}
