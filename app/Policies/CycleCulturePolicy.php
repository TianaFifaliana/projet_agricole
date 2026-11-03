<?php

namespace App\Policies;

use App\Models\CycleCulture;
use App\Models\User;
use App\Policies\Concerns\VerifieExploitation;

class CycleCulturePolicy
{
    use VerifieExploitation;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CycleCulture $cycle): bool
    {
        return $this->accesAutorise($user, $cycle);
    }

    public function create(User $user): bool
    {
        return $user->isAdministrateur() || $user->isResponsable();
    }

    public function update(User $user, CycleCulture $cycle): bool
    {
        return $this->accesAutorise($user, $cycle)
            && ($user->isAdministrateur() || $user->isResponsable());
    }

    public function delete(User $user, CycleCulture $cycle): bool
    {
        return $this->update($user, $cycle);
    }

    public function enregistrerRecolte(User $user, CycleCulture $cycle): bool
    {
        return $this->accesAutorise($user, $cycle);
    }
}
