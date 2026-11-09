<?php

namespace App\Policies;

use App\Models\CampaignWaterBudget;
use App\Models\User;
use App\Policies\Concerns\VerifieExploitation;

class CampaignWaterBudgetPolicy
{
    use VerifieExploitation;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CampaignWaterBudget $budget): bool
    {
        return $this->accesAutorise($user, $budget);
    }

    public function create(User $user): bool
    {
        return $user->isAdministrateur() || $user->isResponsable();
    }

    public function update(User $user, CampaignWaterBudget $budget): bool
    {
        return $this->accesAutorise($user, $budget)
            && ($user->isAdministrateur() || $user->isResponsable());
    }

    public function delete(User $user, CampaignWaterBudget $budget): bool
    {
        return $this->update($user, $budget);
    }
}
