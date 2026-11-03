@extends('layouts.app')

@section('title', 'Élevage · AgriSaaS')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Élevage</h1>
        <p class="text-sm text-slate-500">Cheptel de l'exploitation (polyculture-élevage)</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Identifiant</th>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Espèce</th>
                    <th class="px-4 py-3">Race</th>
                    <th class="px-4 py-3">Sexe</th>
                    <th class="px-4 py-3">Naissance</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Événements</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($animaux as $animal)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $animal->identifiant }}</td>
                        <td class="px-4 py-3">{{ $animal->nom ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $animal->espece }}</td>
                        <td class="px-4 py-3">{{ $animal->race ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $animal->sexe }}</td>
                        <td class="px-4 py-3">{{ $animal->date_naissance?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $animal->statut }}</td>
                        <td class="px-4 py-3">{{ $animal->evenements_sanitaires_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500">Aucun animal.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $animaux->links() }}</div>
@endsection
