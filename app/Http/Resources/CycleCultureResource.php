<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CycleCultureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campagne' => $this->campagne,
            'statut' => $this->statut->value,
            'statut_label' => $this->statut->label(),
            'date_semis' => $this->date_semis?->toDateString(),
            'date_recolte_prevue' => $this->date_recolte_prevue?->toDateString(),
            'date_recolte_reelle' => $this->date_recolte_reelle?->toDateString(),
            'surface_ha' => (float) $this->surface_ha,
            'rendement_obtenu_tha' => $this->rendement_obtenu_tha !== null ? (float) $this->rendement_obtenu_tha : null,
            'quantite_recoltee_kg' => $this->quantite_recoltee_kg !== null ? (float) $this->quantite_recoltee_kg : null,
            'parcelle' => new ParcelleResource($this->whenLoaded('parcelle')),
            'culture' => $this->whenLoaded('culture', fn (): array => [
                'id' => $this->culture->id,
                'nom' => $this->culture->nom,
                'variete' => $this->culture->variete,
            ]),
            'interventions_count' => $this->whenCounted('interventions'),
            'lots_stock_count' => $this->whenCounted('lotsStock'),
        ];
    }
}
