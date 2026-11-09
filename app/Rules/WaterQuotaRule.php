<?php

namespace App\Rules;

use App\Models\CycleCulture;
use App\Services\WaterBudgetService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WaterQuotaRule implements ValidationRule
{
    public function __construct(
        private readonly CycleCulture $campaign,
        private readonly float $volumeDemande,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $service = app(WaterBudgetService::class);
        $budget = $service->budgetFor($this->campaign);

        if ($budget === null) {
            $fail("Aucun budget d'eau n'est alloué pour cette campagne.");

            return;
        }

        $restant = $service->getRemainingVolume($budget);

        if ($this->volumeDemande > $restant + 0.0001) {
            $fail(sprintf(
                "Le quota d'eau restant est de %s m³ ; l'irrigation demandée consomme %s m³.",
                number_format($restant, 2, ',', ' '),
                number_format($this->volumeDemande, 2, ',', ' '),
            ));
        }
    }
}
