@extends('layouts.app')

@section('title', 'Cycles culturaux · AgriSaaS')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Cycles culturaux</h1>
            <p class="text-sm text-slate-500">Semis, suivis et récoltes par parcelle</p>
        </div>
        @can('create', App\Models\CycleCulture::class)
            <a href="{{ route('cycles.create') }}" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Ouvrir un cycle
            </a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Campagne</th>
                    <th class="px-4 py-3">Parcelle</th>
                    <th class="px-4 py-3">Culture</th>
                    <th class="px-4 py-3">Semis</th>
                    <th class="px-4 py-3">Surface</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Interventions</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($cycles as $cycle)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $cycle->campagne }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $cycle->parcelle->code }}</td>
                        <td class="px-4 py-3">
                            {{ $cycle->culture->nom }}
                            @if ($cycle->culture->variete)
                                <span class="text-xs text-slate-400">({{ $cycle->culture->variete }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $cycle->date_semis?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $cycle->surface_ha, 2, ',', ' ') }} ha</td>
                        <td class="px-4 py-3">
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs">{{ $cycle->statut->label() }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $cycle->interventions_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('cycles.show', $cycle) }}" class="text-emerald-700 hover:underline">Détail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500">Aucun cycle cultural.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $cycles->links() }}</div>
@endsection
