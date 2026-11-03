<?php

namespace App\Services;

use App\Models\JournalAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * @param  array<string, mixed>|null  $anciennesValeurs
     * @param  array<string, mixed>|null  $nouvellesValeurs
     */
    public function journaliser(
        string $action,
        Model $entite,
        ?array $anciennesValeurs = null,
        ?array $nouvellesValeurs = null,
        ?int $exploitationId = null,
    ): JournalAudit {
        $user = Auth::user();

        return JournalAudit::create([
            'exploitation_id' => $exploitationId ?? $entite->getAttribute('exploitation_id'),
            'user_id' => $user instanceof User ? $user->getKey() : null,
            'action' => $action,
            'entite' => $entite::class,
            'entite_id' => $entite->getKey(),
            'anciennes_valeurs' => $anciennesValeurs,
            'nouvelles_valeurs' => $nouvellesValeurs,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
