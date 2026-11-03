@extends('layouts.app')

@section('title', 'Interventions · AgriSaaS')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Interventions</h1>
        <p class="text-sm text-slate-500">Journal des travaux culturaux</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Parcelle</th>
                    <th class="px-4 py-3">Cycle</th>
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
                        <td class="px-4 py-3 font-mono text-xs">{{ $intervention->parcelle?->code }}</td>
                        <td class="px-4 py-3">
                            @if ($intervention->cycleCulture)
                                <a href="{{ route('cycles.show', $intervention->cycleCulture) }}" class="text-emerald-700 hover:underline">
                                    {{ $intervention->cycleCulture->campagne }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $intervention->type->label() }}</td>
                        <td class="px-4 py-3">{{ $intervention->quantite !== null ? $intervention->quantite.' '.$intervention->unite : '—' }}</td>
                        <td class="px-4 py-3">{{ $intervention->cout !== null ? number_format((float) $intervention->cout, 2, ',', ' ').' €' : '—' }}</td>
                        <td class="px-4 py-3">{{ $intervention->operateur?->nomComplet() ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $intervention->description ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500">Aucune intervention enregistrée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $interventions->links() }}</div>
@endsection
