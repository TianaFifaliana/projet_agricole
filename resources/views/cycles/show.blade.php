@extends('layouts.app')

@section('title', 'Cycle cultural · AgriSaaS')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500">Cycle cultural</p>
            <h1 class="text-2xl font-semibold">{{ $cycle->culture->nom }} — {{ $cycle->campagne }}</h1>
            <p class="text-sm text-slate-500">
                Parcelle {{ $cycle->parcelle->code }} · {{ $cycle->parcelle->nom }} ·
                {{ number_format((float) $cycle->surface_ha, 2, ',', ' ') }} ha ·
                <span class="font-medium">{{ $cycle->statut->label() }}</span>
            </p>
        </div>
        <a href="{{ route('cycles.index') }}" class="text-sm text-slate-500 hover:underline">Retour aux cycles</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-400">Semis</p>
            <p class="mt-1 text-lg font-semibold">{{ $cycle->date_semis?->format('d/m/Y') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-400">Récolte réelle</p>
            <p class="mt-1 text-lg font-semibold">{{ $cycle->date_recolte_reelle?->format('d/m/Y') ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-400">Rendement</p>
            <p class="mt-1 text-lg font-semibold">
                {{ $cycle->rendement_obtenu_tha !== null ? number_format((float) $cycle->rendement_obtenu_tha, 2, ',', ' ').' t/ha' : '—' }}
            </p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Enregistrer une intervention</h2>
            @can('create', App\Models\Intervention::class)
                <form method="POST" action="{{ route('interventions.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="parcelle_id" value="{{ $cycle->parcelle_id }}">
                    <input type="hidden" name="cycle_culture_id" value="{{ $cycle->id }}">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="type" class="block text-sm font-medium">Type</label>
                            <select id="type" name="type" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                                @foreach (App\Enums\TypeIntervention::cases() as $type)
                                    @continue($type === App\Enums\TypeIntervention::Recolte)
                                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="date_intervention" class="block text-sm font-medium">Date</label>
                            <input id="date_intervention" name="date_intervention" type="date"
                                   value="{{ old('date_intervention', now()->toDateString()) }}" required
                                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="quantite" class="block text-sm font-medium">Quantité</label>
                            <input id="quantite" name="quantite" type="number" step="0.01" min="0"
                                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="unite" class="block text-sm font-medium">Unité</label>
                            <input id="unite" name="unite" value="kg" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="cout" class="block text-sm font-medium">Coût</label>
                            <input id="cout" name="cout" type="number" step="0.01" min="0"
                                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium">Description</label>
                        <textarea id="description" name="description" rows="2" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm"></textarea>
                    </div>
                    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                        Ajouter l'intervention
                    </button>
                </form>
            @endcan
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Enregistrer la récolte</h2>
            @if ($cycle->estRecolte())
                <p class="mt-3 rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                    Récolte déjà enregistrée : {{ number_format((float) $cycle->quantite_recoltee_kg, 2, ',', ' ') }} kg.
                    Un lot de stock traçable a été créé.
                </p>
            @else
                @can('enregistrerRecolte', $cycle)
                    <form method="POST" action="{{ route('cycles.recolte', $cycle) }}" class="mt-4 space-y-3">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="quantite_kg" class="block text-sm font-medium">Quantité (kg)</label>
                                <input id="quantite_kg" name="quantite_kg" type="number" step="0.01" min="0.01" required
                                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label for="date_recolte" class="block text-sm font-medium">Date de récolte</label>
                                <input id="date_recolte" name="date_recolte" type="date"
                                       value="{{ now()->toDateString() }}" required
                                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        </div>
                        <div>
                            <label for="description_recolte" class="block text-sm font-medium">Observation</label>
                            <textarea id="description_recolte" name="description" rows="2"
                                      class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm"></textarea>
                        </div>
                        <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Récolter et créer le lot
                        </button>
                    </form>
                @endcan
            @endif
        </section>
    </div>

    <section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold">Interventions</h2>
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Quantité</th>
                    <th class="px-4 py-3">Coût</th>
                    <th class="px-4 py-3">Opérateur</th>
                    <th class="px-4 py-3">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($interventions as $intervention)
                    <tr>
                        <td class="px-4 py-3">{{ $intervention->date_intervention?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $intervention->type->label() }}</td>
                        <td class="px-4 py-3">{{ $intervention->quantite !== null ? $intervention->quantite.' '.$intervention->unite : '—' }}</td>
                        <td class="px-4 py-3">{{ $intervention->cout !== null ? number_format((float) $intervention->cout, 2, ',', ' ').' €' : '—' }}</td>
                        <td class="px-4 py-3">{{ $intervention->operateur?->nomComplet() ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $intervention->description ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Aucune intervention.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold">Lots de stock issus de ce cycle</h2>
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Code lot</th>
                    <th class="px-4 py-3">Entrée</th>
                    <th class="px-4 py-3">Initial</th>
                    <th class="px-4 py-3">Restant</th>
                    <th class="px-4 py-3">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($lots as $lot)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $lot->code_lot }}</td>
                        <td class="px-4 py-3">{{ $lot->date_entree?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $lot->quantite_initiale, 2, ',', ' ') }} {{ $lot->unite }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $lot->quantite_restante, 2, ',', ' ') }} {{ $lot->unite }}</td>
                        <td class="px-4 py-3">{{ $lot->statut->label() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">Aucun lot pour l'instant.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
