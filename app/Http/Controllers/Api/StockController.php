<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LotStockResource;
use App\Models\LotStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', LotStock::class);

        $lots = LotStock::query()
            ->with(['intrant', 'parcelle', 'cycleCulture'])
            ->when($request->filled('statut'), fn ($query) => $query->where('statut', $request->string('statut')))
            ->when($request->filled('intrant_id'), fn ($query) => $query->where('intrant_id', $request->integer('intrant_id')))
            ->when($request->filled('parcelle_id'), fn ($query) => $query->where('parcelle_id', $request->integer('parcelle_id')))
            ->when($request->boolean('disponible'), fn ($query) => $query->disponibles())
            ->orderByDesc('date_entree')
            ->paginate(min($request->integer('per_page', 20), 100));

        return LotStockResource::collection($lots);
    }

    public function show(LotStock $lot): LotStockResource
    {
        $this->authorize('view', $lot);

        return new LotStockResource($lot->load(['intrant', 'parcelle', 'cycleCulture']));
    }

    public function mouvements(LotStock $lot): JsonResponse
    {
        $this->authorize('view', $lot);

        $mouvements = $lot->mouvementsStock()
            ->with('user')
            ->orderByDesc('date_mouvement')
            ->get()
            ->map(fn ($mouvement): array => [
                'id' => $mouvement->getKey(),
                'type' => $mouvement->type->value,
                'type_label' => $mouvement->type->label(),
                'quantite' => (float) $mouvement->quantite,
                'quantite_avant' => (float) $mouvement->quantite_avant,
                'quantite_apres' => (float) $mouvement->quantite_apres,
                'motif' => $mouvement->motif,
                'reference' => $mouvement->reference,
                'date_mouvement' => $mouvement->date_mouvement?->toIso8601String(),
                'utilisateur' => $mouvement->user?->nomComplet(),
            ]);

        return response()->json(['data' => $mouvements]);
    }
}
