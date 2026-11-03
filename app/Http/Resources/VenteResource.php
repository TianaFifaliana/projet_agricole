<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'date_vente' => $this->date_vente?->toDateString(),
            'statut' => $this->statut->value,
            'statut_label' => $this->statut->label(),
            'total_ht' => (float) $this->total_ht,
            'tva' => (float) $this->tva,
            'total_ttc' => (float) $this->total_ttc,
            'validee_at' => $this->validee_at?->toIso8601String(),
            'partenaire' => $this->whenLoaded('partenaire', fn (): array => [
                'id' => $this->partenaire->id,
                'nom' => $this->partenaire->nom,
            ]),
            'lignes' => LigneVenteResource::collection($this->whenLoaded('lignes')),
        ];
    }
}
