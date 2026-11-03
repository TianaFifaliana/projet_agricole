@extends('layouts.app')

@section('title', 'Stocks · AgriSaaS')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Stocks et traçabilité</h1>
        <p class="text-sm text-slate-500">Lots, quantités restantes et seuils d'alerte</p>
    </div>

    @if (! empty($alertes))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <h2 class="text-sm font-semibold text-amber-900">Alertes de seuil</h2>
            <ul class="mt-2 space-y-1 text-sm text-amber-800">
                @foreach ($alertes as $alerte)
                    <li>
                        {{ $alerte['nom'] }} :
                        {{ number_format($alerte['stock_disponible'], 2, ',', ' ') }} / {{ number_format($alerte['seuil_alerte'], 2, ',', ' ') }} {{ $alerte['unite'] }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold">Intrants suivis</h2>
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Intrant</th>
                    <th class="px-4 py-3">Catégorie</th>
                    <th class="px-4 py-3">Unité</th>
                    <th class="px-4 py-3">Stock disponible</th>
                    <th class="px-4 py-3">Seuil d'alerte</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($intrants as $intrant)
                    <tr>
                        <td class="px-4 py-3">{{ $intrant->nom }}</td>
                        <td class="px-4 py-3 capitalize">{{ $intrant->categorie }}</td>
                        <td class="px-4 py-3">{{ $intrant->unite }}</td>
                        <td class="px-4 py-3">{{ number_format($intrant->quantiteDisponible(), 2, ',', ' ') }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $intrant->seuil_alerte, 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold">Lots de stock</h2>
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Code lot</th>
                    <th class="px-4 py-3">Intrant / origine</th>
                    <th class="px-4 py-3">Parcelle</th>
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
                        <td class="px-4 py-3">
                            {{ $lot->intrant?->nom ?? 'Récolte' }}
                            <span class="text-xs text-slate-400">{{ $lot->origine }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $lot->parcelle?->code ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $lot->date_entree?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $lot->quantite_initiale, 2, ',', ' ') }} {{ $lot->unite }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $lot->quantite_restante, 2, ',', ' ') }} {{ $lot->unite }}</td>
                        <td class="px-4 py-3">{{ $lot->statut->label() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">Aucun lot de stock.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="mt-4">{{ $lots->links() }}</div>
@endsection
