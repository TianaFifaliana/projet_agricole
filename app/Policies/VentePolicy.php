<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vente;
use App\Policies\Concerns\VerifieExploitation;

class VentePolicy
{
    use VerifieExploitation;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vente $vente): bool
    {
        return $this->accesAutorise($user, $vente);
    }

    public function create(User $user): bool
    {
        return $user->exploitation_id !== null || $user->isAdministrateur();
    }

    public function update(User $user, Vente $vente): bool
    {
        return $this->accesAutorise($user, $vente)
            && ($user->isAdministrateur() || $user->isResponsable());
    }

    public function delete(User $user, Vente $vente): bool
    {
        return $this->update($user, $vente);
    }

    public function valider(User $user, Vente $vente): bool
    {
        return $this->accesAutorise($user, $vente)
            && ($user->isAdministrateur() || $user->isResponsable());
    }

    public function annuler(User $user, Vente $vente): bool
    {
        return $this->valider($user, $vente);
    }
}
