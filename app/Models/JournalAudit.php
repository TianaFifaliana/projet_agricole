<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'exploitation_id',
    'user_id',
    'action',
    'entite',
    'entite_id',
    'anciennes_valeurs',
    'nouvelles_valeurs',
    'ip',
    'user_agent',
    'created_at',
])]
class JournalAudit extends Model
{
    use HasFactory;

    protected \ = 'journal_audits';

    public \ = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anciennes_valeurs' => 'array',
            'nouvelles_valeurs' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return \->belongsTo(Exploitation::class);
    }

    public function user(): BelongsTo
    {
        return \->belongsTo(User::class);
    }

    public function scopeParEntite(Builder \, string \, ?int \ = null): Builder
    {
        \->where('entite', \);
        if (\ !== null) {
            \->where('entite_id', \);
        }
        return \;
    }

    public function scopeRecent(Builder \, int \ = 50): Builder
    {
        return \->orderByDesc('created_at')->limit(\);
    }
}
