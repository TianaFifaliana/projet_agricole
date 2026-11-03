<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion · AgriSaaS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center px-4">
    <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-semibold text-emerald-700">AgriSaaS</h1>
        <p class="mt-1 text-sm text-slate-500">Gestion d'exploitation agricole</p>

        @if ($errors->any())
            <div class="mt-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium">Adresse e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium">Mot de passe</label>
                <input id="password" name="password" type="password" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Se souvenir de moi
            </label>
            <button type="submit" class="w-full rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Se connecter
            </button>
        </form>

        <p class="mt-6 text-xs text-slate-400">
            Démo : responsable@agrisaas.test · mot de passe <span class="font-mono">password</span>
        </p>
    </div>
</body>
</html>
