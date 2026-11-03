<?php

namespace App\Policies;

use App\Models\Intervention;
use App\Models\User;
use App\Policies\Concerns\VerifieExploitation;

class InterventionPolicy
{
    use VerifieExploitation;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Intervention $intervention): bool
    {
        return $this->accesAutorise($user, $intervention);
    }

    public function create(User $user): bool
    {
        return $user->exploitation_id !== null || $user->isAdministrateur();
    }

    public function update(User $user, Intervention $intervention): bool
    {
        return $this->accesAutorise($user, $intervention)
            && ($user->isAdministrateur() || $user->isResponsable());
    }

    public function delete(User $user, Intervention $intervention): bool
    {
        return $this->update($user, $intervention);
    }
}
