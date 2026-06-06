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
     * The single election row. Created on first access so the app always has one scrutin.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    /**
     * Resolve the scrutin mode from a candidate count (rule 4).
     * > threshold => Mode A (select exactly threshold); <= threshold => Mode B (auto-elect).
     * Returns null when there are no candidates yet (mode undetermined).
     */
    public function resolveMode(int $candidateCount): ?string
    {
        if ($candidateCount === 0) {
            return null;
        }

        return $candidateCount > $this->candidate_threshold
            ? self::MODE_SELECT
            : self::MODE_AUTO;
    }

    /**
     * Recompute the mode from the live candidate count and flag candidates as
     * auto-elected when (and only when) Mode B applies (rule 4). Centralised here so
     * every candidate change keeps the scrutin state consistent server-side.
     */
    public function syncModeFromCandidates(): void
    {
        $count = Candidate::query()->count();
        $mode = $this->resolveMode($count);

        $this->mode = $mode;
        $this->save();

        Candidate::query()->update(['auto_elected' => $mode === self::MODE_AUTO]);
    }

    /**
     * Number of seats a Mode A voter must fill — exactly the threshold (rule 4).
     */
    public function requiredSelections(): int
    {
        return $this->candidate_threshold;
    }

    /**
     * Voting is only accepted while the window is open AND the QR gate is active (rule 6).
     */
    public function isVotingOpen(): bool
    {
        return $this->window_open && $this->qr_active;
    }
}
