<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AgriSaaS')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans text-slate-900">
    @auth
        <nav class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-4 px-4 py-3">
                <a href="{{ route('dashboard') }}" class="text-lg font-semibold text-emerald-700">AgriSaaS</a>
                <div class="flex flex-wrap gap-1 text-sm">
                    <a class="rounded px-2 py-1 hover:bg-slate-100" href="{{ route('parcelles.index') }}">Parcelles</a>
                    <a class="rounded px-2 py-1 hover:bg-slate-100" href="{{ route('cycles.index') }}">Cycles</a>
                    <a class="rounded px-2 py-1 hover:bg-slate-100" href="{{ route('interventions.index') }}">Interventions</a>
                    <a class="rounded px-2 py-1 hover:bg-slate-100" href="{{ route('irrigations.index') }}">Irrigation</a>
                    <a class="rounded px-2 py-1 hover:bg-slate-100" href="{{ route('water-budgets.index') }}">Quotas d'eau</a>
                    <a class="rounded px-2 py-1 hover:bg-slate-100" href="{{ route('stocks.index') }}">Stocks</a>
                    <a class="rounded px-2 py-1 hover:bg-slate-100" href="{{ route('ventes.index') }}">Ventes</a>
                    <a class="rounded px-2 py-1 hover:bg-slate-100" href="{{ route('animaux.index') }}">Élevage</a>
                    <a class="rounded px-2 py-1 hover:bg-slate-100" href="{{ route('alertes.index') }}">Alertes</a>
                </div>
                <div class="ml-auto flex items-center gap-3 text-sm">
                    <span class="text-slate-500">
                        {{ auth()->user()->nomComplet() }}
                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">{{ auth()->user()->role->label() }}</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded border border-slate-300 px-3 py-1 hover:bg-slate-100">Déconnexion</button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="mx-auto max-w-7xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Corrigez les erreurs suivantes :</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
