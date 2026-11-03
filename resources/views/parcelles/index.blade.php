@extends('layouts.app')

@section('title', 'Parcelles · AgriSaaS')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Parcelles</h1>
            <p class="text-sm text-slate-500">Module cultures & parcelles</p>
        </div>
        @can('create', App\Models\Parcelle::class)
            <a href="{{ route('parcelles.create') }}" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Nouvelle parcelle
            </a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Surface</th>
                    <th class="px-4 py-3">Type de sol</th>
                    <th class="px-4 py-3">Cycles</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($parcelles as $parcelle)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $parcelle->code }}</td>
                        <td class="px-4 py-3">{{ $parcelle->nom }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $parcelle->superficie_ha, 2, ',', ' ') }} ha</td>
                        <td class="px-4 py-3">{{ $parcelle->type_sol ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $parcelle->cycles_culturaux_count }}</td>
                        <td class="px-4 py-3">
                            @if ($parcelle->actif)
                                <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">Active</span>
                            @else
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $parcelle)
                                <a href="{{ route('parcelles.edit', $parcelle) }}" class="text-emerald-700 hover:underline">Modifier</a>
                            @endcan
                            @can('delete', $parcelle)
                                <form method="POST" action="{{ route('parcelles.destroy', $parcelle) }}" class="inline" onsubmit="return confirm('Supprimer cette parcelle ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-3 text-red-600 hover:underline">Supprimer</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">Aucune parcelle enregistrée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $parcelles->links() }}</div>
@endsection
