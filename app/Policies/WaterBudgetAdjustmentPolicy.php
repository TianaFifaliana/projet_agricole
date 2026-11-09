<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaterBudgetAdjustment;
use App\Policies\Concerns\VerifieExploitation;

class WaterBudgetAdjustmentPolicy
{
    use VerifieExploitation;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WaterBudgetAdjustment $adjustment): bool
    {
        return $this->accesAutorise($user, $adjustment);
    }

    public function create(User $user): bool
    {
        return $user->exploitation_id !== null || $user->isAdministrateur();
    }

    public function approve(User $user, WaterBudgetAdjustment $adjustment): bool
    {
        return $this->accesAutorise($user, $adjustment)
            && ($user->isAdministrateur() || $user->isResponsable());
    }

    public function update(User $user, WaterBudgetAdjustment $adjustment): bool
    {
        return $this->approve($user, $adjustment);
    }

    public function delete(User $user, WaterBudgetAdjustment $adjustment): bool
    {
        return $this->approve($user, $adjustment);
    }
}
