<?php

namespace App\Http\Requests;

use App\Models\CycleCulture;

class StoreCampaignIrrigationRequest extends StoreIrrigationRequest
{
    /**
     * La campagne provient du paramètre de route {campaign}.
     */
    protected function campaignId(): int|string|null
    {
        return $this->route('campaign')?->getKey();
    }

    /**
     * Le quota d'eau est contrôlé dans la transaction d'IrrigationService
     * (réponse 409 avec message JSON en cas de dépassement) plutôt qu'en
     * pré-validation, afin de passer par le même verrou que l'application web.
     *
     * @return array<int, mixed>
     */
    protected function reglesQuota(CycleCulture $campaign, float $volume): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['campaign_id']);

        return $rules;
    }
}
