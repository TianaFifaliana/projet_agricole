<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterventionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'date_intervention' => $this->date_intervention?->toDateString(),
            'quantite' => $this->quantite !== null ? (float) $this->quantite : null,
            'unite' => $this->unite,
            'cout' => $this->cout !== null ? (float) $this->cout : null,
            'description' => $this->description,
            'parcelle' => $this->whenLoaded('parcelle', fn (): array => [
                'id' => $this->parcelle->id,
                'code' => $this->parcelle->code,
            ]),
            'operateur' => $this->whenLoaded('operateur', fn (): ?array => $this->operateur === null ? null : [
                'id' => $this->operateur->id,
                'nom_complet' => $this->operateur->nomComplet(),
            ]),
        ];
    }
}
