@extends('layouts.app')

@section('title', 'Irrigation · AgriSaaS')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Gestion de l'irrigation</h1>
            <p class="text-sm text-slate-500">Suivi des apports d'eau et du quota par campagne</p>
        </div>
        @can('create', App\Models\Irrigation::class)
            <a href="{{ route('irrigations.create') }}" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Enregistrer une irrigation
            </a>
        @endcan
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($budgets as $budget)
            @php
                $pourcentage = min(100, $budget->pourcentageConsomme());
                $couleur = $budget->estEpuise()
                    ? 'bg-red-500'
                    : ($budget->depasseSeuilAlerte() ? 'bg-amber-500' : 'bg-emerald-500');
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold">Campagne {{ $budget->campaign?->campagne }}</p>
                        <p class="text-xs text-slate-500">Rizière {{ $budget->campaign?->parcelle?->code ?? '—' }}</p>
                    </div>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $budget->statut->label() }}</span>
                </div>

                <div class="mt-3 flex items-baseline justify-between text-xs text-slate-500">
                    <span>{{ number_format((float) $budget->volume_consomme_m3, 2, ',', ' ') }} m³ consommés</span>
                    <span>{{ number_format($budget->pourcentageConsomme(), 1, ',', ' ') }} %</span>
                </div>
                <div class="mt-1 h-2 w-full rounded bg-slate-200">
                    <div class="h-2 rounded {{ $couleur }}" style="width: {{ $pourcentage }}%"></div>
                </div>
                <div class="mt-2 flex justify-between text-xs text-slate-500">
                    <span>Alloué : {{ number_format((float) $budget->volume_alloue_m3, 2, ',', ' ') }} m³</span>
                    <span>Restant : {{ number_format($budget->volumeRestant(), 2, ',', ' ') }} m³</span>
                </div>
                @if ($budget->aRallongeEnAttente())
                    <p class="mt-2 text-xs font-medium text-amber-600">Une demande de rallonge est en attente.</p>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                Aucun budget d'eau alloué pour le moment.
            </div>
        @endforelse
    </div>

    <form method="GET" action="{{ route('irrigations.index') }}" class="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-6">
        <label class="text-xs font-medium text-slate-600">
            Campagne
            <select name="campaign_id" class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-sm">
                <option value="">Toutes</option>
                @foreach ($campaigns as $campagne)
                    <option value="{{ $campagne->getKey() }}" @selected(($filtres['campaign_id'] ?? null) == $campagne->getKey())>
                        {{ $campagne->campagne }} · {{ $campagne->parcelle?->code }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="text-xs font-medium text-slate-600">
            Rizière
            <select name="production_unit_id" class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-sm">
                <option value="">Toutes</option>
                @foreach ($parcelles as $parcelle)
                    <option value="{{ $parcelle->getKey() }}" @selected(($filtres['production_unit_id'] ?? null) == $parcelle->getKey())>
                        {{ $parcelle->code }} · {{ $parcelle->nom }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="text-xs font-medium text-slate-600">
            Source
            <select name="water_source_id" class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-sm">
                <option value="">Toutes</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->getKey() }}" @selected(($filtres['water_source_id'] ?? null) == $source->getKey())>
                        {{ $source->nom }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="text-xs font-medium text-slate-600">
            Du
            <input type="date" name="date_debut" value="{{ $filtres['date_debut'] ?? '' }}" class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-sm">
        </label>

        <label class="text-xs font-medium text-slate-600">
            Au
            <input type="date" name="date_fin" value="{{ $filtres['date_fin'] ?? '' }}" class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-sm">
        </label>

        <div class="flex items-end gap-2">
            <button type="submit" class="rounded bg-slate-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-900">Filtrer</button>
            <a href="{{ route('irrigations.index') }}" class="rounded border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Réinitialiser</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Campagne</th>
                    <th class="px-4 py-3">Rizière</th>
                    <th class="px-4 py-3">Source</th>
                    <th class="px-4 py-3">Durée</th>
                    <th class="px-4 py-3">Débit</th>
                    <th class="px-4 py-3">Volume</th>
                    <th class="px-4 py-3">Coût</th>
                    <th class="px-4 py-3">Agent</th>
                    <th class="px-4 py-3">Observation</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($irrigations as $irrigation)
                    <tr>
                        <td class="px-4 py-3">{{ $irrigation->date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $irrigation->campaign?->campagne ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $irrigation->productionUnit?->code ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $irrigation->waterSource?->nom ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $irrigation->duree_minutes }} min</td>
                        <td class="px-4 py-3">{{ number_format((float) $irrigation->debit_m3_h, 2, ',', ' ') }} m³/h</td>
                        <td class="px-4 py-3 font-medium">{{ number_format((float) $irrigation->volume_m3, 2, ',', ' ') }} m³</td>
                        <td class="px-4 py-3">{{ $irrigation->cout !== null ? number_format((float) $irrigation->cout, 2, ',', ' ').' €' : '—' }}</td>
                        <td class="px-4 py-3">{{ $irrigation->user?->nomComplet() ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $irrigation->observation ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-6 text-center text-slate-500">Aucune irrigation enregistrée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $irrigations->links() }}</div>
@endsection
