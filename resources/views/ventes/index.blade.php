@extends('layouts.app')

@section('title', 'Ventes · AgriSaaS')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Ventes</h1>
            <p class="text-sm text-slate-500">Brouillons, validation et décrément du stock</p>
        </div>
        @can('create', App\Models\Vente::class)
            <a href="{{ route('ventes.create') }}" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Nouvelle vente
            </a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Numéro</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Total TTC</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ventes as $vente)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $vente->numero }}</td>
                        <td class="px-4 py-3">{{ $vente->date_vente?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $vente->partenaire?->nom }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs">{{ $vente->statut->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $vente->total_ttc, 2, ',', ' ') }} €</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('ventes.show', $vente) }}" class="text-emerald-700 hover:underline">Détail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Aucune vente.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $ventes->links() }}</div>
@endsection
