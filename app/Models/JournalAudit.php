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

    protected $table = 'journal_audits';

    public $timestamps = false;

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
        return $this->belongsTo(Exploitation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeParEntite(Builder $query, string $entite, ?int $entiteId = null): Builder
    {
        $query->where('entite', $entite);

        if ($entiteId !== null) {
            $query->where('entite_id', $entiteId);
        }

        return $query;
    }

    public function scopeRecent(Builder $query, int $limite = 50): Builder
    {
        return $query->orderByDesc('created_at')->limit($limite);
    }
}
