<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Thin helper for the append-only audit journal (CLAUDE.md rule 7).
 * Captures the acting admin (if any), the client IP, and optional structured context.
 */
class AuditLogger
{
    public static function log(string $action, ?string $description = null, array $context = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'context' => $context ?: null,
            'ip_address' => Request::ip(),
        ]);
    }
}
