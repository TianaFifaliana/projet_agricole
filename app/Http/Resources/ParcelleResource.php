<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParcelleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'nom' => $this->nom,
            'superficie_ha' => (float) $this->superficie_ha,
            'type_sol' => $this->type_sol,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'actif' => (bool) $this->actif,
            'cycles_culturaux_count' => $this->whenCounted('cyclesCulturaux'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
