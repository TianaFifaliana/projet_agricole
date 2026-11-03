@extends('layouts.app')

@section('title', 'Nouvelle vente · AgriSaaS')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Nouvelle vente</h1>
        <p class="text-sm text-slate-500">La vente est créée en brouillon ; le stock est décrémenté à la validation.</p>
    </div>

    <form method="POST" action="{{ route('ventes.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                @if ($exploitations->isNotEmpty())
                    <div>
                        <label for="exploitation_id" class="block text-sm font-medium">Exploitation</label>
                        <select id="exploitation_id" name="exploitation_id" required
                                class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            <option value="">— Sélectionner —</option>
                            @foreach ($exploitations as $exploitation)
                                <option value="{{ $exploitation->id }}" @selected(old('exploitation_id') == $exploitation->id)>
                                    {{ $exploitation->code }} — {{ $exploitation->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label for="partenaire_id" class="block text-sm font-medium">Client</label>
                    <select id="partenaire_id" name="partenaire_id" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($partenaires as $partenaire)
                            <option value="{{ $partenaire->id }}" @selected(old('partenaire_id') == $partenaire->id)>{{ $partenaire->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_vente" class="block text-sm font-medium">Date de vente</label>
                    <input id="date_vente" name="date_vente" type="date" value="{{ old('date_vente', now()->toDateString()) }}" required
                           class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="taux_tva" class="block text-sm font-medium">Taux de TVA (%)</label>
                    <input id="taux_tva" name="taux_tva" type="number" step="0.01" min="0" max="30" value="{{ old('taux_tva', 0) }}"
                           class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="notes" class="block text-sm font-medium">Notes</label>
                    <input id="notes" name="notes" value="{{ old('notes') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Lignes de vente</h2>
                <button type="button" onclick="ajouterLigne()" class="rounded border border-slate-300 px-3 py-1 text-sm hover:bg-slate-50">
                    Ajouter une ligne
                </button>
            </div>
            <div id="lignes" class="mt-4 space-y-3"></div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Créer le brouillon
            </button>
            <a href="{{ route('ventes.index') }}" class="text-sm text-slate-500 hover:underline">Annuler</a>
        </div>
    </form>

    <script>
        const lotsDisponibles = @json($lots->map(fn ($lot) => [
            'id' => $lot->id,
            'code' => $lot->code_lot,
            'designation' => $lot->intrant?->nom ?? 'Récolte '.$lot->code_lot,
            'unite' => $lot->unite,
            'prix' => $lot->prix_unitaire,
        ])->values());

        let indexLigne = 0;

        function ajouterLigne() {
            const i = indexLigne++;
            const options = [
                '<option value="">— Sans lot (frais / service) —</option>',
                ...lotsDisponibles.map((lot) => `<option value="${lot.id}" data-designation="${lot.designation}" data-unite="${lot.unite}" data-prix="${lot.prix ?? ''}">${lot.code} · ${lot.designation} (${lot.unite})</option>`),
            ].join('');

            const html = `
                <div class="grid gap-2 rounded border border-slate-200 p-3 sm:grid-cols-12">
                    <select name="lignes[${i}][lot_stock_id]" onchange="remplirLigne(this)" class="rounded border border-slate-300 px-2 py-1 text-sm sm:col-span-4">
                        ${options}
                    </select>
                    <input name="lignes[${i}][designation]" placeholder="Désignation" required class="rounded border border-slate-300 px-2 py-1 text-sm sm:col-span-3">
                    <input name="lignes[${i}][quantite]" type="number" step="0.01" min="0.01" placeholder="Qté" required class="rounded border border-slate-300 px-2 py-1 text-sm sm:col-span-1">
                    <input name="lignes[${i}][unite]" placeholder="Unité" required class="rounded border border-slate-300 px-2 py-1 text-sm sm:col-span-1">
                    <input name="lignes[${i}][prix_unitaire]" type="number" step="0.01" min="0" placeholder="PU" required class="rounded border border-slate-300 px-2 py-1 text-sm sm:col-span-2">
                    <button type="button" onclick="this.closest('div').remove()" class="text-sm text-red-600 sm:col-span-1">Supprimer</button>
                </div>`;

            document.getElementById('lignes').insertAdjacentHTML('beforeend', html);
        }

        function remplirLigne(select) {
            const option = select.options[select.selectedIndex];
            const ligne = select.closest('div');

            if (!option.value) {
                return;
            }

            ligne.querySelector('[name$="[designation]"]').value = option.dataset.designation;
            ligne.querySelector('[name$="[unite]"]').value = option.dataset.unite;

            if (option.dataset.prix) {
                ligne.querySelector('[name$="[prix_unitaire]"]').value = option.dataset.prix;
            }
        }

        ajouterLigne();
    </script>
@endsection
