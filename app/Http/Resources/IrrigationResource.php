<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IrrigationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'date' => $this->date?->toDateString(),
            'duree_minutes' => $this->duree_minutes,
            'debit_m3_h' => (float) $this->debit_m3_h,
            'volume_m3' => (float) $this->volume_m3,
            'cout' => $this->cout !== null ? (float) $this->cout : null,
            'observation' => $this->observation,
            'parcelle' => $this->whenLoaded('productionUnit', fn (): array => [
                'id' => $this->productionUnit->id,
                'code' => $this->productionUnit->code,
            ]),
            'source_eau' => $this->whenLoaded('waterSource', fn (): array => [
                'id' => $this->waterSource->id,
                'nom' => $this->waterSource->nom,
            ]),
            'utilisateur' => $this->whenLoaded('user', fn (): ?array => $this->user === null ? null : [
                'id' => $this->user->id,
                'nom_complet' => $this->user->nomComplet(),
            ]),
        ];
    }
}
