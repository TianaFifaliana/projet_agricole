<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    use HasApiTokens, HasFactory, Notifiable;

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
}
