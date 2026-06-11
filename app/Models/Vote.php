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
 * DB uniqueness guarantees one vote per company per election round.
 */
class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'company_id',
        'assembly_company_id',
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

    protected static function booted(): void
    {
        static::creating(function (Vote $vote) {
            $election = $vote->election_id
                ? Election::query()->find($vote->election_id)
                : (Election::active() ?? Election::current());

            $vote->election_id ??= $election->id;

            if (! $vote->assembly_company_id && $vote->company_id) {
                $company = Company::find($vote->company_id);
                if ($company) {
                    $assemblyCompany = AssemblyCompany::firstOrCreate(
                        ['assembly_id' => $election->assembly_id, 'company_id' => $company->id],
                        [
                            'name' => $company->name,
                            'normalized_name' => $company->normalized_name,
                            'survey_2025' => (bool) ($company->survey_2025 ?? false),
                            'dues_2025' => (bool) ($company->dues_2025 ?? false),
                            'new_member_2026' => (bool) ($company->new_member_2026 ?? false),
                            'eligible' => $company->isEligible(),
                        ],
                    );

                    $vote->assembly_company_id = $assemblyCompany->id;
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assemblyCompany(): BelongsTo
    {
        return $this->belongsTo(AssemblyCompany::class);
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function selections(): HasMany
    {
        return $this->hasMany(VoteSelection::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class);
    }

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(Candidate::class, 'vote_selections');
    }

    public function scopeRound(Builder $query, ?int $round): Builder
    {
        return $query->where('round', $round ?? 1);
    }

    public function scopeForElection(Builder $query, Election|int $election): Builder
    {
        return $query->where('election_id', $election instanceof Election ? $election->id : $election);
    }
}
