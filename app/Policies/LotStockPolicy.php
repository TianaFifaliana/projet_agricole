<?php

namespace App\Policies;

use App\Models\LotStock;
use App\Models\User;
use App\Policies\Concerns\VerifieExploitation;

class LotStockPolicy
{
    use VerifieExploitation;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LotStock $lot): bool
    {
        return $this->accesAutorise($user, $lot);
    }

    public function create(User $user): bool
    {
        return $user->isAdministrateur() || $user->isResponsable();
    }

    public function update(User $user, LotStock $lot): bool
    {
        return $this->accesAutorise($user, $lot)
            && ($user->isAdministrateur() || $user->isResponsable());
    }

    public function delete(User $user, LotStock $lot): bool
    {
        return $this->update($user, $lot);
    }
}
