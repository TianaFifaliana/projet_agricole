@extends('layouts.app')

@section('title', 'Alertes · AgriSaaS')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Alertes</h1>
        <p class="text-sm text-slate-500">Seuils de stock et notifications reçues</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Stocks sous seuil</h2>
            @forelse ($alertes as $alerte)
                <div class="mt-3 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    <p class="font-medium">{{ $alerte['nom'] }}</p>
                    <p>
                        Disponible : {{ number_format($alerte['stock_disponible'], 2, ',', ' ') }} {{ $alerte['unite'] }}
                        · Seuil : {{ number_format($alerte['seuil_alerte'], 2, ',', ' ') }} {{ $alerte['unite'] }}
                    </p>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-500">Aucun stock sous le seuil.</p>
            @endforelse
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Notifications</h2>
            @forelse ($notifications as $notification)
                <div class="mt-3 rounded border border-slate-200 px-3 py-2 text-sm">
                    <p class="font-medium">
                        {{ $notification->data['intrant'] ?? 'Notification' }}
                    </p>
                    <p class="text-slate-600">
                        Stock : {{ $notification->data['quantite_disponible'] ?? '—' }}
                        {{ $notification->data['unite'] ?? '' }}
                        · Seuil : {{ $notification->data['seuil_alerte'] ?? '—' }}
                    </p>
                    <p class="text-xs text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-500">Aucune notification.</p>
            @endforelse
        </section>
    </div>
@endsection
