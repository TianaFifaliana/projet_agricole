<?php

namespace App\Http\Controllers;

use App\Enums\StatutCycleCulture;
use App\Exceptions\RegleMetierException;
use App\Http\Requests\StoreWaterBudgetAdjustmentRequest;
use App\Http\Requests\StoreWaterBudgetRequest;
use App\Models\CampaignWaterBudget;
use App\Models\CycleCulture;
use App\Models\WaterBudgetAdjustment;
use App\Services\WaterBudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaterBudgetController extends Controller
{
    public function __construct(private WaterBudgetService $budgets) {}

    public function index(): View
    {
        $this->authorize('viewAny', CampaignWaterBudget::class);

        return view('water-budgets.index', [
            'budgets' => CampaignWaterBudget::query()
                ->with(['campaign.parcelle', 'adjustments.demandeur', 'adjustments.validateur'])
                ->orderByDesc('created_at')
                ->get(),
            'campaigns' => CycleCulture::query()
                ->where('statut', StatutCycleCulture::EnCours->value)
                ->doesntHave('waterBudget')
                ->orderByDesc('date_semis')
                ->get(),
        ]);
    }

    public function store(StoreWaterBudgetRequest $request): RedirectResponse
    {
        $campaign = CycleCulture::query()->findOrFail($request->validated('campaign_id'));

        try {
            $this->budgets->allocateBudget(
                $campaign,
                (float) $request->validated('volume_alloue_m3'),
                $request->validated('seuil_alerte_pct') !== null
                    ? (float) $request->validated('seuil_alerte_pct')
                    : null,
            );
        } catch (RegleMetierException $exception) {
            return back()->withInput()->withErrors(['budget' => $exception->getMessage()]);
        }

        return back()->with('status', 'Budget d\'eau alloué à la campagne.');
    }

    public function storeAdjustment(
        StoreWaterBudgetAdjustmentRequest $request,
        CampaignWaterBudget $budget,
    ): RedirectResponse {
        try {
            $this->budgets->requestAdjustment(
                $budget,
                (float) $request->validated('volume_supplementaire_m3'),
                $request->validated('motif'),
                $request->user(),
            );
        } catch (RegleMetierException $exception) {
            return back()->withInput()->withErrors(['adjustment' => $exception->getMessage()]);
        }

        return back()->with('status', 'Demande de rallonge enregistrée.');
    }

    public function approve(Request $request, WaterBudgetAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('approve', $adjustment);

        try {
            $this->budgets->approveAdjustment($adjustment, $request->user(), $request->input('commentaire'));
        } catch (RegleMetierException $exception) {
            return back()->withErrors(['adjustment' => $exception->getMessage()]);
        }

        return back()->with('status', 'Rallonge de quota approuvée.');
    }

    public function refuse(Request $request, WaterBudgetAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('approve', $adjustment);

        try {
            $this->budgets->refuseAdjustment($adjustment, $request->user(), $request->input('commentaire'));
        } catch (RegleMetierException $exception) {
            return back()->withErrors(['adjustment' => $exception->getMessage()]);
        }

        return back()->with('status', 'Rallonge de quota refusée.');
    }
}
