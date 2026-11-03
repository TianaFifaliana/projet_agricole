@extends('layouts.app')

@section('title', 'Administration · AgriSaaS')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Administration de la plateforme</h1>
        <p class="text-sm text-slate-500">Vue transverse de toutes les exploitations</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($exploitations as $exploitation)
            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-mono text-xs text-slate-400">{{ $exploitation->code }}</p>
                        <h2 class="text-lg font-semibold">{{ $exploitation->nom }}</h2>
                        <p class="text-sm text-slate-500">{{ $exploitation->ville }} · {{ $exploitation->superficie_ha }} ha</p>
                    </div>
                    <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">
                        {{ $exploitation->actif ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-slate-400">Utilisateurs</dt>
                        <dd class="text-xl font-semibold">{{ $exploitation->users_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Parcelles</dt>
                        <dd class="text-xl font-semibold">{{ $exploitation->parcelles_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Cycles</dt>
                        <dd class="text-xl font-semibold">{{ $exploitation->cycles_culturaux_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Ventes</dt>
                        <dd class="text-xl font-semibold">{{ $exploitation->ventes_count }}</dd>
                    </div>
                </dl>

                <div class="mt-4 flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('parcelles.index') }}" class="text-emerald-700 hover:underline">Parcelles</a>
                    <a href="{{ route('cycles.index') }}" class="text-emerald-700 hover:underline">Cycles</a>
                    <a href="{{ route('stocks.index') }}" class="text-emerald-700 hover:underline">Stocks</a>
                    <a href="{{ route('ventes.index') }}" class="text-emerald-700 hover:underline">Ventes</a>
                </div>
            </section>
        @endforeach
    </div>
@endsection
