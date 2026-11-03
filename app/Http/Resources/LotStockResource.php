<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotStockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code_lot' => $this->code_lot,
            'quantite_initiale' => (float) $this->quantite_initiale,
            'quantite_restante' => (float) $this->quantite_restante,
            'unite' => $this->unite,
            'date_entree' => $this->date_entree?->toDateString(),
            'date_peremption' => $this->date_peremption?->toDateString(),
            'origine' => $this->origine,
            'statut' => $this->statut->value,
            'statut_label' => $this->statut->label(),
            'intrant' => $this->whenLoaded('intrant', fn (): ?array => $this->intrant === null ? null : [
                'id' => $this->intrant->id,
                'nom' => $this->intrant->nom,
                'categorie' => $this->intrant->categorie,
            ]),
            'parcelle' => $this->whenLoaded('parcelle', fn (): ?array => $this->parcelle === null ? null : [
                'id' => $this->parcelle->id,
                'code' => $this->parcelle->code,
                'nom' => $this->parcelle->nom,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
