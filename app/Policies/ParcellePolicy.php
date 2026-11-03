<?php

namespace App\Policies;

use App\Models\Parcelle;
use App\Models\User;
use App\Policies\Concerns\VerifieExploitation;

class ParcellePolicy
{
    use VerifieExploitation;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Parcelle $parcelle): bool
    {
        return $this->accesAutorise($user, $parcelle);
    }

    public function create(User $user): bool
    {
        return $user->isAdministrateur() || $user->isResponsable();
    }

    public function update(User $user, Parcelle $parcelle): bool
    {
        return $this->accesAutorise($user, $parcelle)
            && ($user->isAdministrateur() || $user->isResponsable());
    }

    public function delete(User $user, Parcelle $parcelle): bool
    {
        return $this->update($user, $parcelle);
    }
}
