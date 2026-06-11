<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One vote/scrutin inside an AG. Board votes keep the original CA election flow;
 * question votes group several Oui/Non/Abstention sub-votes.
 */
class Election extends Model
{
    use HasFactory;

    public const TYPE_BOARD = 'board';
    public const TYPE_QUESTIONS = 'questions';
    public const ACTIVE_SLOT_GLOBAL = 'global';

    public const MODE_SELECT = 'A';   // > threshold candidates: voter picks exactly <threshold>.
    public const MODE_AUTO = 'B';     // <= threshold candidates: all auto-elected, no ballot.

    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_RUNOFF_OPEN = 'runoff_open';
    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'assembly_id',
        'name',
        'type',
        'display_order',
        'status',
        'mode',
        'candidate_threshold',
        'current_round',
        'runoff_candidate_ids',
        'runoff_seats',
        'window_open',
        'qr_active',
        'active_slot',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'candidate_threshold' => 'integer',
            'display_order' => 'integer',
            'current_round' => 'integer',
            'runoff_candidate_ids' => 'array',
            'runoff_seats' => 'integer',
            'window_open' => 'boolean',
            'qr_active' => 'boolean',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Compatibility helper for the default CA vote in the latest AG.
     */
    public static function current(): self
    {
        $assembly = Assembly::current();

        return static::query()->firstOrCreate(
            ['assembly_id' => $assembly->id, 'type' => self::TYPE_BOARD],
            [
                'name' => 'Élection du Conseil d’Administration 2026',
                'status' => self::STATUS_DRAFT,
                'current_round' => 1,
                'display_order' => 1,
            ],
        );
    }

    public static function active(): ?self
    {
        return static::query()
            ->with('assembly')
            ->where('active_slot', self::ACTIVE_SLOT_GLOBAL)
            ->first()
            ?? static::query()
                ->with('assembly')
                ->where('window_open', true)
                ->where('qr_active', true)
                ->whereIn('status', [self::STATUS_OPEN, self::STATUS_RUNOFF_OPEN])
                ->orderByDesc('opened_at')
                ->orderByDesc('id')
                ->first();
    }

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class)
            ->with('assemblyCompany')
            ->orderBy('display_order')
            ->orderBy('name');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ElectionQuestion::class)->orderBy('display_order')->orderBy('id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function isBoardVote(): bool
    {
        return $this->type === self::TYPE_BOARD;
    }

    public function isQuestionsVote(): bool
    {
        return $this->type === self::TYPE_QUESTIONS;
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
        if (! $this->isBoardVote()) {
            $this->mode = null;
            $this->save();

            return;
        }

        $count = $this->candidates()->count();
        $mode = $this->resolveMode($count);

        $this->mode = $mode;
        if ($this->status === self::STATUS_DRAFT && $mode !== null) {
            $this->status = self::STATUS_READY;
        }
        $this->save();

        $this->candidates()->update(['auto_elected' => $mode === self::MODE_AUTO]);
    }

    /**
     * Number of seats a Mode A voter must fill. In the main vote that is the threshold;
     * in a runoff (round 2+) it is the number of contested seats (rule 4 + tiebreaker).
     */
    public function requiredSelections(): int
    {
        return $this->isRunoff() ? (int) $this->runoff_seats : $this->candidate_threshold;
    }

    /**
     * True once a tiebreaker round has been launched (round 2+ with a restricted ballot).
     */
    public function isRunoff(): bool
    {
        return (int) $this->current_round > 1 && ! empty($this->runoff_candidate_ids);
    }

    /**
     * Candidates appearing on the current ballot: all candidates in the main vote,
     * only the tied candidates during a runoff.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Candidate>
     */
    public function ballotCandidates()
    {
        $query = $this->candidates();

        if ($this->isRunoff()) {
            $query->whereIn('id', $this->runoff_candidate_ids);
        }

        return $query->get();
    }

    /**
     * Voting is only accepted while the window is open AND the QR gate is active (rule 6).
     */
    public function isVotingOpen(): bool
    {
        return $this->window_open
            && $this->qr_active
            && ($this->active_slot === self::ACTIVE_SLOT_GLOBAL
                || ! static::query()->where('active_slot', self::ACTIVE_SLOT_GLOBAL)->exists())
            && in_array($this->status, [self::STATUS_OPEN, self::STATUS_RUNOFF_OPEN], true);
    }

    public function canEditConfiguration(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_READY], true)
            && ! $this->window_open;
    }

    public function canOpenMainVote(): bool
    {
        if (! $this->isBoardVote()) {
            return false;
        }

        return $this->status === self::STATUS_READY
            && $this->mode !== null
            && (int) ($this->current_round ?? 1) === 1
            && $this->qr_active
            && $this->candidates()->exists()
            && $this->assembly->eligibleCompanies()->exists()
            && (static::active()?->is($this) ?? true);
    }

    public function canOpenQuestionsVote(): bool
    {
        if (! $this->isQuestionsVote()) {
            return false;
        }

        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_READY], true)
            && $this->qr_active
            && $this->questions()->exists()
            && $this->assembly->eligibleCompanies()->exists()
            && (static::active()?->is($this) ?? true);
    }

    public function canOpen(): bool
    {
        return $this->isBoardVote()
            ? $this->canOpenMainVote()
            : $this->canOpenQuestionsVote();
    }

    public function canLaunchRunoff(): bool
    {
        return $this->status === self::STATUS_CLOSED
            && ! $this->window_open
            && $this->mode === self::MODE_SELECT;
    }

    public function canExportFinalResults(): bool
    {
        return in_array($this->status, [self::STATUS_CLOSED, self::STATUS_FINALIZED], true)
            && ! $this->window_open;
    }

    public function isReadOnly(): bool
    {
        return in_array($this->status, [
            self::STATUS_OPEN,
            self::STATUS_CLOSED,
            self::STATUS_RUNOFF_OPEN,
            self::STATUS_FINALIZED,
        ], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_READY => 'Prêt',
            self::STATUS_OPEN => 'Vote ouvert',
            self::STATUS_CLOSED => 'Clôturé',
            self::STATUS_RUNOFF_OPEN => 'Départage ouvert',
            self::STATUS_FINALIZED => 'Finalisé',
            default => 'Brouillon',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_QUESTIONS => 'Questions Oui / Non / Abstention',
            default => 'Élection du Conseil d’Administration',
        };
    }
}
