<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LigneVenteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lot_stock_id' => $this->lot_stock_id,
            'designation' => $this->designation,
            'quantite' => (float) $this->quantite,
            'unite' => $this->unite,
            'prix_unitaire' => (float) $this->prix_unitaire,
            'montant' => (float) $this->montant,
        ];
    }
}
