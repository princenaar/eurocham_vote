<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'title',
        'description',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class);
    }
}
