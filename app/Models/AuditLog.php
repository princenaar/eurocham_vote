<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only activity journal for audit/dispute, admin-only (CLAUDE.md rule 7).
 */
class AuditLog extends Model
{
    use HasFactory;

    // Append-only: rows carry created_at only, never updated.
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'context',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
