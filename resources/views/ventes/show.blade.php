@extends('layouts.app')

@section('title', 'Vente '.$vente->numero.' · AgriSaaS')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500">Vente</p>
            <h1 class="text-2xl font-semibold">{{ $vente->numero }}</h1>
            <p class="text-sm text-slate-500">
                {{ $vente->date_vente?->format('d/m/Y') }} · {{ $vente->partenaire?->nom }} ·
                <span class="font-medium">{{ $vente->statut->label() }}</span>
            </p>
        </div>
        <a href="{{ route('ventes.index') }}" class="text-sm text-slate-500 hover:underline">Retour aux ventes</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-400">Total HT</p>
            <p class="mt-1 text-lg font-semibold">{{ number_format((float) $vente->total_ht, 2, ',', ' ') }} €</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-400">TVA</p>
            <p class="mt-1 text-lg font-semibold">{{ number_format((float) $vente->tva, 2, ',', ' ') }} €</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-400">Total TTC</p>
            <p class="mt-1 text-lg font-semibold">{{ number_format((float) $vente->total_ttc, 2, ',', ' ') }} €</p>
        </div>
    </div>

    @if ($vente->estBrouillon() || $vente->statut === App\Enums\StatutVente::Validee)
        <div class="mt-6 flex flex-wrap gap-3">
            @if ($vente->estBrouillon())
                @can('valider', $vente)
                    <form method="POST" action="{{ route('ventes.valider', $vente) }}">
                        @csrf
                        <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Valider la vente
                        </button>
                    </form>
                @endcan
            @endif

            @can('annuler', $vente)
                <form method="POST" action="{{ route('ventes.annuler', $vente) }}" onsubmit="return confirm('Annuler cette vente ?');">
                    @csrf
                    <button type="submit" class="rounded border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                        Annuler la vente
                    </button>
                </form>
            @endcan
        </div>
    @endif

    <section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold">Lignes</h2>
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Désignation</th>
                    <th class="px-4 py-3">Lot</th>
                    <th class="px-4 py-3">Quantité</th>
                    <th class="px-4 py-3">Prix unitaire</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($vente->lignes as $ligne)
                    <tr>
                        <td class="px-4 py-3">{{ $ligne->designation }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $ligne->lotStock?->code_lot ?? '—' }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $ligne->quantite, 2, ',', ' ') }} {{ $ligne->unite }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $ligne->prix_unitaire, 2, ',', ' ') }} €</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $ligne->montant, 2, ',', ' ') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    @if ($vente->mouvementsStock->isNotEmpty())
        <section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold">Traçabilité des mouvements</h2>
            </div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Lot</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Quantité</th>
                        <th class="px-4 py-3">Avant</th>
                        <th class="px-4 py-3">Après</th>
                        <th class="px-4 py-3">Motif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($vente->mouvementsStock as $mouvement)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $mouvement->lotStock?->code_lot ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $mouvement->type->label() }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $mouvement->quantite, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $mouvement->quantite_avant, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $mouvement->quantite_apres, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $mouvement->motif }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection
