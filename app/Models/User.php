<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'exploitation_id',
    'nom',
    'prenom',
    'email',
    'telephone',
    'role',
    'actif',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'actif' => 'boolean',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return \->belongsTo(Exploitation::class);
    }

    public function interventions(): HasMany
    {
        return \->hasMany(Intervention::class, 'operateur_id');
    }

    public function ventes(): HasMany
    {
        return \->hasMany(Vente::class);
    }

    public function journalAudits(): HasMany
    {
        return \->hasMany(JournalAudit::class);
    }

    public function mouvementsStock(): HasMany
    {
        return \->hasMany(MouvementStock::class);
    }

    public function isAdministrateur(): bool
    {
        return \->role === UserRole::Administrateur;
    }

    public function isResponsable(): bool
    {
        return \->role === UserRole::Responsable;
    }

    public function isAgent(): bool
    {
        return \->role === UserRole::Agent;
    }

    public function nomComplet(): string
    {
        return trim(\->prenom.' '.\->nom);
    }

    public function scopeActifs(\)
    {
        return \->where('actif', true);
    }
}
