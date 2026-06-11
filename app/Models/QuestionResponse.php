<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'vote_id',
        'election_question_id',
        'answer',
    ];

    protected function casts(): array
    {
        return [
            'answer' => 'boolean',
        ];
    }

    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ElectionQuestion::class, 'election_question_id');
    }
}
