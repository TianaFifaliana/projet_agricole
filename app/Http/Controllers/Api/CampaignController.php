<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignIrrigationRequest;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\IrrigationResource;
use App\Models\CycleCulture;
use App\Services\IrrigationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CampaignController extends Controller
{
    public function __construct(private IrrigationService $irrigations) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CycleCulture::class);

        $campaigns = CycleCulture::query()
            ->with(['parcelle', 'culture', 'waterBudget'])
            ->when($request->filled('campagne'), fn ($query) => $query->where('campagne', $request->string('campagne')))
            ->when($request->filled('statut'), fn ($query) => $query->where('statut', $request->string('statut')))
            ->when($request->filled('parcelle_id'), fn ($query) => $query->where('parcelle_id', $request->integer('parcelle_id')))
            ->orderByDesc('date_semis')
            ->paginate(min($request->integer('per_page', 15), 100));

        return CampaignResource::collection($campaigns);
    }

    public function show(CycleCulture $campaign): CampaignResource
    {
        $this->authorize('view', $campaign);

        return new CampaignResource($campaign->load(['parcelle', 'culture', 'waterBudget']));
    }

    public function irrigations(Request $request, CycleCulture $campaign): AnonymousResourceCollection
    {
        $this->authorize('view', $campaign);

        $irrigations = $campaign->irrigations()
            ->with(['productionUnit', 'waterSource', 'user'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 20), 100));

        return IrrigationResource::collection($irrigations);
    }

    public function storeIrrigation(StoreCampaignIrrigationRequest $request, CycleCulture $campaign): JsonResponse
    {
        $irrigation = $this->irrigations->recordIrrigation(
            [...$request->validated(), 'campaign_id' => $campaign->getKey()],
            $request->user(),
        );

        return (new IrrigationResource($irrigation->load(['productionUnit', 'waterSource', 'user'])))
            ->response()
            ->setStatusCode(201);
    }
}
