<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParcelleRequest;
use App\Http\Requests\UpdateParcelleRequest;
use App\Http\Resources\ParcelleResource;
use App\Models\Parcelle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ParcelleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Parcelle::class);

        $parcelles = Parcelle::query()
            ->withCount('cyclesCulturaux')
            ->when($request->boolean('actif'), fn ($query) => $query->where('actif', true))
            ->orderBy('code')
            ->paginate(min($request->integer('per_page', 15), 100));

        return ParcelleResource::collection($parcelles);
    }

    public function show(Parcelle $parcelle): ParcelleResource
    {
        $this->authorize('view', $parcelle);

        return new ParcelleResource($parcelle->loadCount('cyclesCulturaux'));
    }

    public function store(StoreParcelleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $exploitationId = $request->user()->exploitation_id ?? (int) ($data['exploitation_id'] ?? 0);

        abort_if($exploitationId === 0, 422, 'Une exploitation est requise.');

        unset($data['exploitation_id']);

        $parcelle = Parcelle::create([
            ...$data,
            'exploitation_id' => $exploitationId,
        ]);

        return (new ParcelleResource($parcelle))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateParcelleRequest $request, Parcelle $parcelle): ParcelleResource
    {
        $parcelle->update($request->validated());

        return new ParcelleResource($parcelle->refresh());
    }

    public function destroy(Parcelle $parcelle): JsonResponse
    {
        $this->authorize('delete', $parcelle);

        $parcelle->delete();

        return response()->json(status: 204);
    }
}
