@extends('layouts.app')

@section('title', 'Tableau de bord · AgriSaaS')

@php
    $libellesCategories = [
        'semence' => 'Semences',
        'engrais' => 'Engrais',
        'phytosanitaire' => 'Phytosanitaires',
        'aliment' => 'Aliments',
        'autre' => 'Autres',
    ];
    $stocksParCategorie = collect($statistiques['stocks'])->groupBy('categorie')->sortKeys();
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Tableau de bord</h1>
        <p class="text-sm text-slate-500">
            {{ $exploitation?->nom }} · campagne {{ now()->year }}
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Campagnes actives</p>
            <p class="mt-2 text-3xl font-semibold">{{ $statistiques['campagnes_actives']['total'] }}</p>
            <p class="text-xs text-slate-400">{{ number_format($statistiques['recoltes_kg'], 0, ',', ' ') }} kg récoltés cette année</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Rizières</p>
            <p class="mt-2 text-3xl font-semibold">{{ $statistiques['parcelles']['total'] }}</p>
            <p class="text-xs text-slate-400">{{ number_format($statistiques['parcelles']['surface_ha'], 2, ',', ' ') }} ha au total</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Superficie exploitée</p>
            <p class="mt-2 text-3xl font-semibold">{{ number_format($statistiques['campagnes_actives']['superficie_ha'], 2, ',', ' ') }} ha</p>
            <p class="text-xs text-slate-400">sur {{ number_format($statistiques['parcelles']['surface_ha'], 2, ',', ' ') }} ha de rizières</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Dépenses</p>
            <p class="mt-2 text-3xl font-semibold text-rose-600">{{ number_format($marge['depenses'], 2, ',', ' ') }} €</p>
            <p class="text-xs text-slate-400">toutes catégories</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Recettes</p>
            <p class="mt-2 text-3xl font-semibold text-emerald-700">{{ number_format($marge['recettes_ht'], 2, ',', ' ') }} €</p>
            <p class="text-xs text-slate-400">ventes validées, HT</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Marge simplifiée</p>
            <p class="mt-2 text-3xl font-semibold {{ $marge['marge'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                {{ number_format($marge['marge'], 2, ',', ' ') }} €
            </p>
            <p class="text-xs text-slate-400">
                @if ($marge['taux_marge'] !== null)
                    taux de marge {{ number_format($marge['taux_marge'], 2, ',', ' ') }} %
                @else
                    aucune recette enregistrée
                @endif
            </p>
        </div>
    </div>

    <section class="mt-8">
        <h2 class="text-lg font-semibold">Indicateurs riziculture</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Consommation d'eau par hectare</p>
                <p class="mt-2 text-3xl font-semibold">
                    {{ $indicateurs['consommation_eau_m3_ha'] !== null ? number_format($indicateurs['consommation_eau_m3_ha'], 2, ',', ' ') : '—' }}
                    <span class="text-base font-normal text-slate-400">m³/ha</span>
                </p>
                <p class="text-xs text-slate-400">volume d'eau utilisé / superficie, toutes campagnes</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Rendement</p>
                <p class="mt-2 text-3xl font-semibold">
                    {{ $indicateurs['rendement_tha'] !== null ? number_format($indicateurs['rendement_tha'], 2, ',', ' ') : '—' }}
                    <span class="text-base font-normal text-slate-400">t/ha</span>
                </p>
                <p class="text-xs text-slate-400">paddy récolté / superficie, toutes campagnes</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Productivité de l'eau</p>
                <p class="mt-2 text-3xl font-semibold">
                    {{ $indicateurs['productivite_eau_kg_m3'] !== null ? number_format($indicateurs['productivite_eau_kg_m3'], 2, ',', ' ') : '—' }}
                    <span class="text-base font-normal text-slate-400">kg/m³</span>
                </p>
                <p class="text-xs text-slate-400">kg de paddy par m³ d'eau consommé</p>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Campagne</th>
                        <th class="px-4 py-3">Parcelle</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Surface (ha)</th>
                        <th class="px-4 py-3 text-right">Eau consommée (m³)</th>
                        <th class="px-4 py-3 text-right">m³/ha</th>
                        <th class="px-4 py-3 text-right">Rendement (t/ha)</th>
                        <th class="px-4 py-3 text-right">kg paddy/m³</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($indicateurs['campagnes'] as $campagne)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="px-4 py-3 font-medium">{{ $campagne['campagne'] }}</td>
                            <td class="px-4 py-3">{{ $campagne['parcelle'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $campagne['statut_label'] }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($campagne['surface_ha'], 2, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($campagne['volume_consomme_m3'], 2, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-right">{{ $campagne['consommation_eau_m3_ha'] !== null ? number_format($campagne['consommation_eau_m3_ha'], 2, ',', ' ') : '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $campagne['rendement_tha'] !== null ? number_format($campagne['rendement_tha'], 2, ',', ' ') : '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $campagne['productivite_eau_kg_m3'] !== null ? number_format($campagne['productivite_eau_kg_m3'], 2, ',', ' ') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-500">Aucune campagne enregistrée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8">
        <h2 class="text-lg font-semibold">Graphiques</h2>
        <div class="mt-3 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="font-semibold">Quota d'eau consommé vs alloué par campagne</h3>
                <div class="mt-4 h-64">
                    <canvas id="chart-quota-eau"></canvas>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="font-semibold">Dépenses vs recettes {{ $evolution['annee'] }}</h3>
                <div class="mt-4 h-64">
                    <canvas id="chart-finances"></canvas>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="font-semibold">Rendement par campagne</h3>
                <div class="mt-4 h-64">
                    <canvas id="chart-rendement"></canvas>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-8">
        <h2 class="text-lg font-semibold">État des stocks</h2>
        <div class="mt-3 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($stocksParCategorie as $categorie => $intrants)
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h3 class="font-semibold">{{ $libellesCategories[$categorie] ?? ucfirst($categorie) }}</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($intrants as $intrant)
                            <li class="flex items-center justify-between gap-2">
                                <span>{{ $intrant['nom'] }}</span>
                                <span class="{{ $intrant['critique'] ? 'font-medium text-amber-700' : 'text-slate-500' }}">
                                    {{ number_format($intrant['stock_disponible'], 2, ',', ' ') }} {{ $intrant['unite'] }}
                                    @if ($intrant['critique'])
                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-xs">sous seuil</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun intrant enregistré.</p>
            @endforelse
        </div>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Alertes stocks critiques</h2>
                <a href="{{ route('alertes.index') }}" class="text-sm text-emerald-700 hover:underline">Tout voir</a>
            </div>
            @forelse ($statistiques['alertes_stock'] as $alerte)
                <div class="mt-3 flex items-center justify-between rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm">
                    <span>{{ $alerte['nom'] }}</span>
                    <span class="text-amber-800">
                        {{ number_format($alerte['stock_disponible'], 2, ',', ' ') }} / {{ number_format($alerte['seuil_alerte'], 2, ',', ' ') }} {{ $alerte['unite'] }}
                    </span>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-500">Aucune alerte : les stocks sont au-dessus des seuils.</p>
            @endforelse
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Alertes quotas d'eau critiques</h2>
                <a href="{{ route('water-budgets.index') }}" class="text-sm text-emerald-700 hover:underline">Tout voir</a>
            </div>
            @forelse ($eau['alertes_quotas'] as $alerte)
                <div class="mt-3 flex items-center justify-between rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm">
                    <span>{{ $alerte['campagne'] }}</span>
                    <span class="text-rose-800">
                        {{ number_format($alerte['pourcentage_consomme'], 2, ',', ' ') }} % consommés
                        (seuil {{ number_format($alerte['seuil_alerte_pct'], 2, ',', ' ') }} %)
                    </span>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-500">Aucune alerte : les quotas d'eau sont sous les seuils.</p>
            @endforelse
        </section>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Activités récentes</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($activites as $activite)
                    <li class="flex items-center justify-between gap-2 rounded border border-slate-200 px-3 py-2">
                        <span>
                            <span class="font-medium">{{ $activite['utilisateur'] }}</span>
                            · {{ $activite['action'] }}
                            <span class="text-slate-400">({{ $activite['entite'] }})</span>
                        </span>
                        <span class="whitespace-nowrap text-xs text-slate-400">{{ $activite['date'] }}</span>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Aucune activité récente.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Actions rapides</h2>
            <div class="mt-3 grid gap-2 text-sm">
                <a class="rounded border border-slate-200 px-3 py-2 hover:bg-slate-50" href="{{ route('cycles.create') }}">Ouvrir un cycle cultural</a>
                <a class="rounded border border-slate-200 px-3 py-2 hover:bg-slate-50" href="{{ route('interventions.index') }}">Consulter les interventions</a>
                <a class="rounded border border-slate-200 px-3 py-2 hover:bg-slate-50" href="{{ route('ventes.create') }}">Créer une vente</a>
                <a class="rounded border border-slate-200 px-3 py-2 hover:bg-slate-50" href="{{ route('stocks.index') }}">Suivre les lots de stock</a>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script id="dashboard-charts-data" type="application/json">{{ Js::from($charts) }}</script>
    @vite('resources/js/dashboard.js')
@endpush
