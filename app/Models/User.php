<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
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
        return $this->belongsTo(Exploitation::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class, 'operateur_id');
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    public function journalAudits(): HasMany
    {
        return $this->hasMany(JournalAudit::class);
    }

    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    public function irrigations(): HasMany
    {
        return $this->hasMany(Irrigation::class);
    }

    public function demandesRallongeEau(): HasMany
    {
        return $this->hasMany(WaterBudgetAdjustment::class, 'demande_par_id');
    }

    public function validationsRallongeEau(): HasMany
    {
        return $this->hasMany(WaterBudgetAdjustment::class, 'valide_par_id');
    }

    public function isAdministrateur(): bool
    {
        return $this->role === UserRole::Administrateur;
    }

    public function isResponsable(): bool
    {
        return $this->role === UserRole::Responsable;
    }

    public function isAgent(): bool
    {
        return $this->role === UserRole::Agent;
    }

    public function nomComplet(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }
}
