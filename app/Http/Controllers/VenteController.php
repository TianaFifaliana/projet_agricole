<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVenteRequest;
use App\Models\Exploitation;
use App\Models\LotStock;
use App\Models\Partenaire;
use App\Models\Vente;
use App\Services\VenteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenteController extends Controller
{
    public function __construct(private VenteService $ventes) {}

    public function index(): View
    {
        $this->authorize('viewAny', Vente::class);

        return view('ventes.index', [
            'ventes' => Vente::query()
                ->with('partenaire')
                ->orderByDesc('date_vente')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Vente::class);

        return view('ventes.create', [
            'partenaires' => Partenaire::query()
                ->where('actif', true)
                ->whereIn('type', ['client', 'les_deux'])
                ->orderBy('nom')
                ->get(),
            'lots' => LotStock::query()
                ->disponibles()
                ->with('intrant')
                ->orderBy('code_lot')
                ->get(),
            'exploitations' => $request->user()->isAdministrateur()
                ? Exploitation::query()->orderBy('nom')->get()
                : collect(),
        ]);
    }

    public function store(StoreVenteRequest $request): RedirectResponse
    {
        $vente = $this->ventes->creer($request->validated(), $request->user());

        return redirect()->route('ventes.show', $vente)
            ->with('status', 'Vente créée en brouillon. Validez-la pour décrémenter le stock.');
    }

    public function show(Vente $vente): View
    {
        $this->authorize('view', $vente);

        $vente->load(['partenaire', 'user', 'lignes.lotStock', 'mouvementsStock']);

        return view('ventes.show', ['vente' => $vente]);
    }

    public function valider(Vente $vente): RedirectResponse
    {
        $this->authorize('valider', $vente);

        $this->ventes->valider($vente, request()->user());

        return redirect()->route('ventes.show', $vente)
            ->with('status', 'Vente validée et stock mis à jour de façon transactionnelle.');
    }

    public function annuler(Vente $vente): RedirectResponse
    {
        $this->authorize('annuler', $vente);

        $this->ventes->annuler($vente, request()->user());

        return redirect()->route('ventes.show', $vente)
            ->with('status', 'Vente annulée et quantités réintégrées au stock.');
    }
}
