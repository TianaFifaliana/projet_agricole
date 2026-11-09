<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaterSource;
use App\Policies\Concerns\VerifieExploitation;

class WaterSourcePolicy
{
    use VerifieExploitation;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WaterSource $source): bool
    {
        return $this->accesAutorise($user, $source);
    }

    public function create(User $user): bool
    {
        return $user->isAdministrateur() || $user->isResponsable();
    }

    public function update(User $user, WaterSource $source): bool
    {
        return $this->accesAutorise($user, $source)
            && ($user->isAdministrateur() || $user->isResponsable());
    }

    public function delete(User $user, WaterSource $source): bool
    {
        return $this->update($user, $source);
    }
}
