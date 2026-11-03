@extends('layouts.app')

@section('title', 'Tableau de bord · AgriSaaS')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Tableau de bord</h1>
        <p class="text-sm text-slate-500">
            {{ $exploitation?->nom }} · campagne {{ now()->year }}
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Parcelles actives</p>
            <p class="mt-2 text-3xl font-semibold">{{ $statistiques['parcelles']['total'] }}</p>
            <p class="text-xs text-slate-400">{{ number_format($statistiques['parcelles']['surface_ha'], 2, ',', ' ') }} ha</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Cycles en cours</p>
            <p class="mt-2 text-3xl font-semibold">{{ $statistiques['cycles_en_cours'] }}</p>
            <p class="text-xs text-slate-400">{{ number_format($statistiques['recoltes_kg'], 2, ',', ' ') }} kg récoltés cette année</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Ventes du mois</p>
            <p class="mt-2 text-3xl font-semibold">{{ $statistiques['ventes_mois']['nombre'] }}</p>
            <p class="text-xs text-slate-400">{{ number_format($statistiques['ventes_mois']['chiffre_affaires'], 2, ',', ' ') }} € TTC</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Animaux actifs</p>
            <p class="mt-2 text-3xl font-semibold">{{ $statistiques['animaux_actifs'] }}</p>
            <p class="text-xs text-slate-400">{{ $statistiques['evenements_sanitaires_mois'] }} événement(s) sanitaire(s) ce mois</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Alertes de stock</h2>
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
