@extends('layouts.app')

@section('title', "Quotas d'eau · AgriSaaS")

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Quotas d'eau par campagne</h1>
        <p class="text-sm text-slate-500">Allocation du budget, suivi de la consommation et rallonges.</p>
    </div>

    @can('create', App\Models\CampaignWaterBudget::class)
        @if ($campaigns->isNotEmpty())
            <form method="POST" action="{{ route('water-budgets.store') }}" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
                @csrf
                <label class="text-xs font-medium text-slate-600 md:col-span-2">
                    Campagne sans budget
                    <select name="campaign_id" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
                        <option value="">— Sélectionner —</option>
                        @foreach ($campaigns as $campagne)
                            <option value="{{ $campagne->getKey() }}" @selected(old('campaign_id') == $campagne->getKey())>
                                {{ $campagne->campagne }} · {{ $campagne->parcelle?->code }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="text-xs font-medium text-slate-600">
                    Volume alloué (m³)
                    <input type="number" step="0.01" min="0.01" name="volume_alloue_m3" value="{{ old('volume_alloue_m3') }}" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
                </label>

                <label class="text-xs font-medium text-slate-600">
                    Seuil d'alerte (%)
                    <input type="number" step="0.01" min="0" max="100" name="seuil_alerte_pct" value="{{ old('seuil_alerte_pct', 80) }}" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
                </label>

                <div class="md:col-span-4">
                    <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Allouer le budget
                    </button>
                </div>
            </form>
        @endif
    @endcan

    <div class="grid gap-4">
        @forelse ($budgets as $budget)
            @php
                $pourcentage = min(100, $budget->pourcentageConsomme());
                $couleur = $budget->estEpuise()
                    ? 'bg-red-500'
                    : ($budget->depasseSeuilAlerte() ? 'bg-amber-500' : 'bg-emerald-500');
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-base font-semibold">Campagne {{ $budget->campaign?->campagne }}</p>
                        <p class="text-xs text-slate-500">Rizière {{ $budget->campaign?->parcelle?->code ?? '—' }}</p>
                    </div>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $budget->statut->label() }}</span>
                </div>

                <div class="mt-4 flex items-baseline justify-between text-xs text-slate-500">
                    <span>{{ number_format((float) $budget->volume_consomme_m3, 2, ',', ' ') }} / {{ number_format((float) $budget->volume_alloue_m3, 2, ',', ' ') }} m³</span>
                    <span>{{ number_format($budget->pourcentageConsomme(), 1, ',', ' ') }} % consommé</span>
                </div>
                <div class="mt-1 h-2.5 w-full rounded bg-slate-200">
                    <div class="h-2.5 rounded {{ $couleur }}" style="width: {{ $pourcentage }}%"></div>
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    Restant : {{ number_format($budget->volumeRestant(), 2, ',', ' ') }} m³ · Seuil d'alerte : {{ number_format((float) $budget->seuil_alerte_pct, 0, ',', ' ') }} %
                </p>

                @can('create', App\Models\WaterBudgetAdjustment::class)
                    @unless ($budget->aRallongeEnAttente())
                        <form method="POST" action="{{ route('water-budgets.adjustments.store', $budget) }}" class="mt-4 grid gap-2 rounded-lg bg-slate-50 p-3 md:grid-cols-4">
                            @csrf
                            <label class="text-xs font-medium text-slate-600">
                                Rallonge (m³)
                                <input type="number" step="0.01" min="0.01" name="volume_supplementaire_m3" class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-sm">
                            </label>
                            <label class="text-xs font-medium text-slate-600 md:col-span-2">
                                Motif
                                <input type="text" name="motif" maxlength="2000" class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-sm">
                            </label>
                            <div class="flex items-end">
                                <button type="submit" class="rounded border border-amber-300 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-800 hover:bg-amber-100">
                                    Demander une rallonge
                                </button>
                            </div>
                        </form>
                    @endunless
                @endcan

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Rallonges</p>
                    <div class="mt-2 space-y-2">
                        @forelse ($budget->adjustments->sortByDesc('created_at') as $adjustment)
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-100 px-3 py-2 text-sm">
                                <div>
                                    <span class="font-medium">+{{ number_format((float) $adjustment->volume_supplementaire_m3, 2, ',', ' ') }} m³</span>
                                    <span class="ml-2 rounded bg-slate-100 px-2 py-0.5 text-xs">{{ $adjustment->statut->label() }}</span>
                                    <p class="text-xs text-slate-500">{{ $adjustment->motif }} — {{ $adjustment->demandeur?->nomComplet() ?? 'Agent' }}</p>
                                    @if (! $adjustment->estEnAttente() && $adjustment->date_decision)
                                        <p class="text-xs text-slate-400">
                                            Décision de {{ $adjustment->validateur?->nomComplet() ?? 'responsable' }} le {{ $adjustment->date_decision->format('d/m/Y') }}
                                        </p>
                                    @endif
                                </div>

                                @if ($adjustment->estEnAttente())
                                    @can('approve', $adjustment)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <form method="POST" action="{{ route('water-budgets.adjustments.approve', $adjustment) }}" class="flex items-center gap-2">
                                                @csrf
                                                <input type="text" name="commentaire" placeholder="Commentaire" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                                <button type="submit" class="rounded bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700">Approuver</button>
                                            </form>
                                            <form method="POST" action="{{ route('water-budgets.adjustments.refuse', $adjustment) }}">
                                                @csrf
                                                <button type="submit" class="rounded border border-red-300 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Refuser</button>
                                            </form>
                                        </div>
                                    @endcan
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">Aucune demande de rallonge.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
                Aucun budget d'eau alloué. Un responsable doit allouer un volume à une campagne active.
            </div>
        @endforelse
    </div>
@endsection
