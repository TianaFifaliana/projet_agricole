<?php

namespace App\Policies;

use App\Models\Irrigation;
use App\Models\User;
use App\Policies\Concerns\VerifieExploitation;

class IrrigationPolicy
{
    use VerifieExploitation;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Irrigation $irrigation): bool
    {
        return $this->accesAutorise($user, $irrigation);
    }

    public function create(User $user): bool
    {
        return $user->exploitation_id !== null || $user->isAdministrateur();
    }

    public function update(User $user, Irrigation $irrigation): bool
    {
        return $this->accesAutorise($user, $irrigation)
            && ($user->isAdministrateur() || $user->isResponsable());
    }

    public function delete(User $user, Irrigation $irrigation): bool
    {
        return $this->update($user, $irrigation);
    }
}
