<?php

namespace App\Http\Controllers;

use App\Enums\EtatSourceEau;
use App\Enums\StatutCycleCulture;
use App\Exceptions\RegleMetierException;
use App\Http\Requests\StoreIrrigationRequest;
use App\Models\CampaignWaterBudget;
use App\Models\CycleCulture;
use App\Models\Irrigation;
use App\Models\Parcelle;
use App\Models\WaterSource;
use App\Services\IrrigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IrrigationController extends Controller
{
    public function __construct(private IrrigationService $irrigations) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Irrigation::class);

        $filtres = $request->validate([
            'campaign_id' => ['nullable', 'integer'],
            'production_unit_id' => ['nullable', 'integer'],
            'water_source_id' => ['nullable', 'integer'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        $query = Irrigation::query()
            ->with(['campaign.parcelle', 'productionUnit', 'waterSource', 'user'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if (! empty($filtres['campaign_id'])) {
            $query->parCampagne((int) $filtres['campaign_id']);
        }

        if (! empty($filtres['production_unit_id'])) {
            $query->parProductionUnit((int) $filtres['production_unit_id']);
        }

        if (! empty($filtres['water_source_id'])) {
            $query->parSource((int) $filtres['water_source_id']);
        }

        if (! empty($filtres['date_debut']) && ! empty($filtres['date_fin'])) {
            $query->parPeriode($filtres['date_debut'], $filtres['date_fin']);
        } elseif (! empty($filtres['date_debut'])) {
            $query->whereDate('date', '>=', $filtres['date_debut']);
        } elseif (! empty($filtres['date_fin'])) {
            $query->whereDate('date', '<=', $filtres['date_fin']);
        }

        return view('irrigations.index', [
            'irrigations' => $query->paginate(20)->withQueryString(),
            'budgets' => CampaignWaterBudget::query()
                ->with(['campaign.parcelle', 'adjustments'])
                ->orderByDesc('created_at')
                ->get(),
            'campaigns' => CycleCulture::query()->orderByDesc('date_semis')->get(),
            'parcelles' => Parcelle::query()->where('actif', true)->orderBy('code')->get(),
            'sources' => WaterSource::query()->orderBy('nom')->get(),
            'filtres' => $filtres,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Irrigation::class);

        return view('irrigations.create', [
            'campaigns' => CycleCulture::query()
                ->where('statut', StatutCycleCulture::EnCours->value)
                ->with('parcelle')
                ->orderByDesc('date_semis')
                ->get(),
            'parcelles' => Parcelle::query()->where('actif', true)->orderBy('code')->get(),
            'sources' => WaterSource::query()
                ->where('etat', EtatSourceEau::Operationnelle->value)
                ->where('actif', true)
                ->orderBy('nom')
                ->get(),
        ]);
    }

    public function store(StoreIrrigationRequest $request): RedirectResponse
    {
        try {
            $irrigation = $this->irrigations->recordIrrigation($request->validated(), $request->user());
        } catch (RegleMetierException $exception) {
            return back()->withInput()->withErrors(['irrigation' => $exception->getMessage()]);
        }

        return redirect()->route('irrigations.index')
            ->with('status', "Irrigation enregistrée ({$irrigation->volume_m3} m³).");
    }
}
