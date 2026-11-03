@extends('layouts.app')

@section('title', 'Nouveau cycle cultural · AgriSaaS')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Ouvrir un cycle cultural</h1>
        <p class="text-sm text-slate-500">Une parcelle ne peut porter qu'un cycle planifié ou en cours à la fois.</p>
    </div>

    @if ($parcelles->isEmpty() || $cultures->isEmpty())
        <div class="rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Créez d'abord au moins une parcelle active et une culture.
            <a href="{{ route('parcelles.create') }}" class="font-semibold underline">Créer une parcelle</a>
        </div>
    @else
        <div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6">
            <form method="POST" action="{{ route('cycles.store') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="parcelle_id" class="block text-sm font-medium">Parcelle</label>
                        <select id="parcelle_id" name="parcelle_id" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($parcelles as $parcelle)
                                <option value="{{ $parcelle->id }}" @selected(old('parcelle_id') == $parcelle->id)>
                                    {{ $parcelle->code }} — {{ $parcelle->nom }} ({{ $parcelle->superficie_ha }} ha)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="culture_id" class="block text-sm font-medium">Culture</label>
                        <select id="culture_id" name="culture_id" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($cultures as $culture)
                                <option value="{{ $culture->id }}" @selected(old('culture_id') == $culture->id)>
                                    {{ $culture->nom }}{{ $culture->variete ? ' — '.$culture->variete : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="campagne" class="block text-sm font-medium">Campagne</label>
                        <input id="campagne" name="campagne" value="{{ old('campagne', now()->year) }}" required
                               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="surface_ha" class="block text-sm font-medium">Surface cultivée (ha)</label>
                        <input id="surface_ha" name="surface_ha" type="number" step="0.01" min="0.01"
                               value="{{ old('surface_ha') }}" required
                               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="date_semis" class="block text-sm font-medium">Date de semis</label>
                        <input id="date_semis" name="date_semis" type="date" value="{{ old('date_semis', now()->toDateString()) }}" required
                               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="date_recolte_prevue" class="block text-sm font-medium">Récolte prévue</label>
                        <input id="date_recolte_prevue" name="date_recolte_prevue" type="date"
                               value="{{ old('date_recolte_prevue') }}"
                               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label for="notes" class="block text-sm font-medium">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Ouvrir le cycle
                    </button>
                    <a href="{{ route('cycles.index') }}" class="text-sm text-slate-500 hover:underline">Annuler</a>
                </div>
            </form>
        </div>
    @endif
@endsection
