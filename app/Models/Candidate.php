<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A candidate for the 2026 Board. Count drives the scrutin mode (CLAUDE.md rule 4).
 */
class Candidate extends Model
{
    use HasFactory;

    private const DEFAULT_PHOTO_PUBLIC_PATH = 'images/candidate-default.svg';

    private const PUBLIC_IMAGE_PATH_PREFIX = 'images/';

    protected $fillable = [
        'election_id',
        'assembly_company_id',
        'name',
        'photo_path',
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

    protected static function booted(): void
    {
        static::creating(function (Candidate $candidate) {
            $candidate->election_id ??= Election::current()->id;
            $election = $candidate->election ?? Election::current();
            $candidate->assembly_company_id ??= $election->assembly->eligibleCompanies()->value('id')
                ?? $election->assembly->companies()->value('id')
                ?? static::fallbackAssemblyCompany($election)->id;
        });
    }

    private static function fallbackAssemblyCompany(Election $election): AssemblyCompany
    {
        $company = Company::firstOrCreate(
            ['normalized_name' => Company::normalizeName('Structure à renseigner')],
            [
                'name' => 'Structure à renseigner',
                'survey_2025' => true,
                'dues_2025' => true,
                'new_member_2026' => false,
            ],
        );

        return AssemblyCompany::firstOrCreate(
            ['assembly_id' => $election->assembly_id, 'company_id' => $company->id],
            [
                'name' => $company->name,
                'normalized_name' => $company->normalized_name,
                'survey_2025' => true,
                'dues_2025' => true,
                'new_member_2026' => false,
                'eligible' => true,
            ],
        );
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function assemblyCompany(): BelongsTo
    {
        return $this->belongsTo(AssemblyCompany::class);
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        if (str_starts_with($this->photo_path, self::PUBLIC_IMAGE_PATH_PREFIX)) {
            return asset($this->photo_path);
        }

        return asset('storage/'.$this->photo_path);
    }

    public function displayPhotoUrl(): string
    {
        return $this->photoUrl() ?? asset(self::DEFAULT_PHOTO_PUBLIC_PATH);
    }

    public function displayPhotoPathForPdf(): string
    {
        if ($this->photo_path) {
            $path = str_starts_with($this->photo_path, self::PUBLIC_IMAGE_PATH_PREFIX)
                ? public_path($this->photo_path)
                : storage_path('app/public/'.$this->photo_path);

            if (file_exists($path)) {
                return $path;
            }
        }

        return public_path(self::DEFAULT_PHOTO_PUBLIC_PATH);
    }

    public function selections(): HasMany
    {
        return $this->hasMany(VoteSelection::class);
    }
}
