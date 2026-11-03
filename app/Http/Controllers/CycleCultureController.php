<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCycleCultureRequest;
use App\Http\Requests\StoreRecolteRequest;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Parcelle;
use App\Services\CycleCultureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CycleCultureController extends Controller
{
    public function __construct(private CycleCultureService $cycles) {}

    public function index(): View
    {
        $this->authorize('viewAny', CycleCulture::class);

        return view('cycles.index', [
            'cycles' => CycleCulture::query()
                ->with(['parcelle', 'culture'])
                ->withCount('interventions')
                ->orderByDesc('date_semis')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', CycleCulture::class);

        return view('cycles.create', [
            'parcelles' => Parcelle::query()->where('actif', true)->orderBy('code')->get(),
            'cultures' => Culture::query()->where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(StoreCycleCultureRequest $request): RedirectResponse
    {
        $cycle = $this->cycles->creer($request->validated());

        return redirect()->route('cycles.show', $cycle)->with('status', 'Cycle cultural ouvert.');
    }

    public function show(CycleCulture $cycle): View
    {
        $this->authorize('view', $cycle);

        $cycle->load([
            'parcelle',
            'culture',
            'interventions.operateur',
            'interventions.intrant',
            'lotsStock',
        ]);

        return view('cycles.show', [
            'cycle' => $cycle,
            'interventions' => $cycle->interventions()->orderByDesc('date_intervention')->get(),
            'lots' => $cycle->lotsStock()->orderByDesc('date_entree')->get(),
        ]);
    }

    public function recolte(StoreRecolteRequest $request, CycleCulture $cycle): RedirectResponse
    {
        $this->cycles->enregistrerRecolte($cycle, $request->validated(), $request->user());

        return redirect()->route('cycles.show', $cycle)
            ->with('status', 'Récolte enregistrée : un lot traçable a été créé en stock.');
    }

}
