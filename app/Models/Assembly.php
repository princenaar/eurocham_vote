<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assembly extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'reference',
        'held_on',
    ];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
        ];
    }

    public static function current(): self
    {
        return static::query()->orderByDesc('held_on')->orderByDesc('id')->first()
            ?? static::query()->create([
                'name' => 'Assemblée Générale EUROCHAM 2026',
                'reference' => 'P01.EUROCHAM.2026',
                'held_on' => '2026-06-18',
            ]);
    }

    public function elections(): HasMany
    {
        return $this->hasMany(Election::class)->orderBy('display_order')->orderBy('id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(AssemblyCompany::class);
    }

    public function eligibleCompanies(): HasMany
    {
        return $this->companies()->where('eligible', true);
    }

    public function canEditCompanies(): bool
    {
        return ! $this->elections()
            ->whereNotIn('status', [Election::STATUS_DRAFT, Election::STATUS_READY])
            ->exists();
    }
}
