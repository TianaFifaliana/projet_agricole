<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInterventionRequest;
use App\Models\Intervention;
use App\Models\Parcelle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InterventionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Intervention::class);

        return view('interventions.index', [
            'interventions' => Intervention::query()
                ->with(['parcelle', 'cycleCulture', 'operateur', 'intrant'])
                ->orderByDesc('date_intervention')
                ->paginate(20),
        ]);
    }

    public function store(StoreInterventionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $exploitationId = $request->user()->exploitation_id
            ?? Parcelle::withoutGlobalScopes()->findOrFail($data['parcelle_id'])->exploitation_id;

        Intervention::create([
            ...$data,
            'exploitation_id' => $exploitationId,
            'operateur_id' => $request->user()->getKey(),
        ]);

        return back()->with('status', 'Intervention enregistrée.');
    }
}
