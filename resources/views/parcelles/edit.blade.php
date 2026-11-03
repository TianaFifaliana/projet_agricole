@extends('layouts.app')

@section('title', 'Modifier la parcelle · AgriSaaS')

@section('content')
    <h1 class="text-2xl font-semibold">Modifier la parcelle {{ $parcelle->code }}</h1>
    <div class="mt-6 max-w-2xl rounded-xl border border-slate-200 bg-white p-6">
        @include('parcelles._form', ['parcelle' => $parcelle])
    </div>
@endsection
