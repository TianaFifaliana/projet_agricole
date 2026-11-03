<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParcelleRequest;
use App\Http\Requests\UpdateParcelleRequest;
use App\Models\Exploitation;
use App\Models\Parcelle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParcelleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Parcelle::class);

        return view('parcelles.index', [
            'parcelles' => Parcelle::query()
                ->withCount('cyclesCulturaux')
                ->orderBy('code')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Parcelle::class);

        return view('parcelles.create', [
            'exploitations' => $request->user()->isAdministrateur()
                ? Exploitation::query()->orderBy('nom')->get()
                : collect(),
        ]);
    }

    public function store(StoreParcelleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['exploitation_id'] = $request->user()->exploitation_id ?? $data['exploitation_id'] ?? null;

        abort_if($data['exploitation_id'] === null, 422, 'Une exploitation est requise.');

        Parcelle::create($data);

        return redirect()->route('parcelles.index')->with('status', 'Parcelle créée.');
    }

    public function edit(Request $request, Parcelle $parcelle): View
    {
        $this->authorize('update', $parcelle);

        return view('parcelles.edit', [
            'parcelle' => $parcelle,
            'exploitations' => $request->user()->isAdministrateur()
                ? Exploitation::query()->orderBy('nom')->get()
                : collect(),
        ]);
    }

    public function update(UpdateParcelleRequest $request, Parcelle $parcelle): RedirectResponse
    {
        $parcelle->update($request->validated());

        return redirect()->route('parcelles.index')->with('status', 'Parcelle mise à jour.');
    }

    public function destroy(Parcelle $parcelle): RedirectResponse
    {
        $this->authorize('delete', $parcelle);

        $parcelle->delete();

        return redirect()->route('parcelles.index')->with('status', 'Parcelle supprimée.');
    }
}
