@php($parcelle = $parcelle ?? null)

<form method="POST"
      action="{{ $parcelle ? route('parcelles.update', $parcelle) : route('parcelles.store') }}"
      class="space-y-4">
    @csrf
    @if ($parcelle)
        @method('PUT')
    @endif

    @if (! $parcelle && isset($exploitations) && $exploitations->isNotEmpty())
        <div>
            <label for="exploitation_id" class="block text-sm font-medium">Exploitation</label>
            <select id="exploitation_id" name="exploitation_id" required
                    class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <option value="">— Sélectionner —</option>
                @foreach ($exploitations as $exploitation)
                    <option value="{{ $exploitation->id }}" @selected(old('exploitation_id') == $exploitation->id)>
                        {{ $exploitation->code }} — {{ $exploitation->nom }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="code" class="block text-sm font-medium">Code</label>
            <input id="code" name="code" value="{{ old('code', $parcelle->code ?? '') }}" required maxlength="30"
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="nom" class="block text-sm font-medium">Nom</label>
            <input id="nom" name="nom" value="{{ old('nom', $parcelle->nom ?? '') }}" required
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="superficie_ha" class="block text-sm font-medium">Superficie (ha)</label>
            <input id="superficie_ha" name="superficie_ha" type="number" step="0.01" min="0.01"
                   value="{{ old('superficie_ha', $parcelle->superficie_ha ?? '') }}" required
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="type_sol" class="block text-sm font-medium">Type de sol</label>
            <input id="type_sol" name="type_sol" value="{{ old('type_sol', $parcelle->type_sol ?? '') }}"
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="latitude" class="block text-sm font-medium">Latitude</label>
            <input id="latitude" name="latitude" type="number" step="0.0000001"
                   value="{{ old('latitude', $parcelle->latitude ?? '') }}"
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="longitude" class="block text-sm font-medium">Longitude</label>
            <input id="longitude" name="longitude" type="number" step="0.0000001"
                   value="{{ old('longitude', $parcelle->longitude ?? '') }}"
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
    </div>

    <input type="hidden" name="actif" value="0">
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="actif" value="1" @checked(old('actif', $parcelle->actif ?? true))>
        Parcelle active
    </label>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            Enregistrer
        </button>
        <a href="{{ route('parcelles.index') }}" class="text-sm text-slate-500 hover:underline">Annuler</a>
    </div>
</form>
