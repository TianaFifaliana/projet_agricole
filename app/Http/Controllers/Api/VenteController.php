<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVenteRequest;
use App\Http\Resources\VenteResource;
use App\Models\Vente;
use App\Services\VenteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VenteController extends Controller
{
    public function __construct(private VenteService $ventes) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Vente::class);

        $ventes = Vente::query()
            ->with('partenaire')
            ->when($request->filled('statut'), fn ($query) => $query->where('statut', $request->string('statut')))
            ->orderByDesc('date_vente')
            ->paginate(min($request->integer('per_page', 15), 100));

        return VenteResource::collection($ventes);
    }

    public function show(Vente $vente): VenteResource
    {
        $this->authorize('view', $vente);

        return new VenteResource($vente->load(['partenaire', 'lignes']));
    }

    public function store(StoreVenteRequest $request): JsonResponse
    {
        $vente = $this->ventes->creer($request->validated(), $request->user());

        return (new VenteResource($vente->load(['partenaire', 'lignes'])))
            ->response()
            ->setStatusCode(201);
    }

    public function valider(Request $request, Vente $vente): VenteResource
    {
        $this->authorize('valider', $vente);

        $vente = $this->ventes->valider($vente, $request->user());

        return new VenteResource($vente->load(['partenaire', 'lignes']));
    }

    public function annuler(Request $request, Vente $vente): VenteResource
    {
        $this->authorize('annuler', $vente);

        $vente = $this->ventes->annuler($vente, $request->user());

        return new VenteResource($vente->load(['partenaire', 'lignes']));
    }
}
