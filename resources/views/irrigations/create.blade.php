@extends('layouts.app')

@section('title', 'Nouvelle irrigation · AgriSaaS')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Enregistrer une irrigation</h1>
        <p class="text-sm text-slate-500">Le volume est calculé automatiquement : débit (m³/h) × durée (min) / 60.</p>
    </div>

    <form method="POST" action="{{ route('irrigations.store') }}" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-6 md:grid-cols-2">
        @csrf

        <label class="text-sm font-medium text-slate-700">
            Campagne active
            <select name="campaign_id" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <option value="">— Sélectionner —</option>
                @foreach ($campaigns as $campagne)
                    <option value="{{ $campagne->getKey() }}" @selected(old('campaign_id') == $campagne->getKey())>
                        {{ $campagne->campagne }} · rizière {{ $campagne->parcelle?->code }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="text-sm font-medium text-slate-700">
            Rizière
            <select name="production_unit_id" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <option value="">— Sélectionner —</option>
                @foreach ($parcelles as $parcelle)
                    <option value="{{ $parcelle->getKey() }}" @selected(old('production_unit_id') == $parcelle->getKey())>
                        {{ $parcelle->code }} · {{ $parcelle->nom }} ({{ number_format((float) $parcelle->superficie_ha, 2, ',', ' ') }} ha)
                    </option>
                @endforeach
            </select>
        </label>

        <label class="text-sm font-medium text-slate-700">
            Source d'eau
            <select name="water_source_id" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <option value="">— Sélectionner —</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->getKey() }}" @selected(old('water_source_id') == $source->getKey())>
                        {{ $source->nom }} · {{ $source->type->label() }} ({{ number_format((float) $source->debit_m3_h, 2, ',', ' ') }} m³/h)
                    </option>
                @endforeach
            </select>
        </label>

        <label class="text-sm font-medium text-slate-700">
            Date
            <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </label>

        <label class="text-sm font-medium text-slate-700">
            Durée (minutes)
            <input type="number" name="duree_minutes" min="1" max="1440" value="{{ old('duree_minutes') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </label>

        <label class="text-sm font-medium text-slate-700">
            Débit (m³/h)
            <input type="number" step="0.01" name="debit_m3_h" min="0.01" value="{{ old('debit_m3_h') }}" placeholder="Débit de la source si vide" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </label>

        <label class="text-sm font-medium text-slate-700">
            Coût (€)
            <input type="number" step="0.01" name="cout" min="0" value="{{ old('cout') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </label>

        <label class="text-sm font-medium text-slate-700 md:col-span-2">
            Observation
            <textarea name="observation" rows="3" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">{{ old('observation') }}</textarea>
        </label>

        <div class="flex items-center gap-3 md:col-span-2">
            <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Enregistrer
            </button>
            <a href="{{ route('irrigations.index') }}" class="text-sm text-slate-600 hover:underline">Annuler</a>
        </div>
    </form>
@endsection
