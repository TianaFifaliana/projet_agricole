<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCycleCultureRequest;
use App\Http\Requests\StoreRecolteRequest;
use App\Http\Resources\CycleCultureResource;
use App\Http\Resources\InterventionResource;
use App\Models\CycleCulture;
use App\Services\CycleCultureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CycleCultureController extends Controller
{
    public function __construct(private CycleCultureService $cycles) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CycleCulture::class);

        $cycles = CycleCulture::query()
            ->with(['parcelle', 'culture'])
            ->withCount(['interventions', 'lotsStock'])
            ->when($request->filled('campagne'), fn ($query) => $query->where('campagne', $request->string('campagne')))
            ->when($request->filled('statut'), fn ($query) => $query->where('statut', $request->string('statut')))
            ->when($request->filled('parcelle_id'), fn ($query) => $query->where('parcelle_id', $request->integer('parcelle_id')))
            ->orderByDesc('date_semis')
            ->paginate(min($request->integer('per_page', 15), 100));

        return CycleCultureResource::collection($cycles);
    }

    public function show(CycleCulture $cycle): CycleCultureResource
    {
        $this->authorize('view', $cycle);

        return new CycleCultureResource(
            $cycle->load(['parcelle', 'culture'])->loadCount(['interventions', 'lotsStock'])
        );
    }

    public function store(StoreCycleCultureRequest $request): JsonResponse
    {
        $cycle = $this->cycles->creer($request->validated());

        return (new CycleCultureResource($cycle->load(['parcelle', 'culture'])))
            ->response()
            ->setStatusCode(201);
    }

    public function recolte(StoreRecolteRequest $request, CycleCulture $cycle): JsonResponse
    {
        $intervention = $this->cycles->enregistrerRecolte(
            $cycle,
            $request->validated(),
            $request->user(),
        );

        return (new InterventionResource($intervention->load(['parcelle', 'operateur'])))
            ->response()
            ->setStatusCode(201);
    }
}
