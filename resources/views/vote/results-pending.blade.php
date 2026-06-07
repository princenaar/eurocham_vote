@extends('layouts.app')

@section('title', 'Résultats — EUROCHAM AG 2026')

{{-- Poll so a projected display flips to the results automatically when the window closes. --}}
@push('head')
    <meta http-equiv="refresh" content="15">
@endpush

@section('content')
    <div class="max-w-xl mx-auto text-center py-12">
        @if ($election->window_open)
            <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                <span class="relative flex h-4 w-4">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-4 w-4 rounded-full bg-emerald-500"></span>
                </span>
            </div>
            <h2 class="font-serif text-3xl font-semibold text-brand-800">
                {{ $isRunoff ? 'Vote de départage en cours' : 'Scrutin en cours' }}
            </h2>
            <p class="mt-3 text-slate-600">
                Les résultats seront affichés automatiquement à la clôture du vote.
            </p>

            <div class="mt-8 inline-flex flex-col rounded-lg border border-slate-200 bg-white px-8 py-5">
                <span class="font-serif text-4xl font-semibold tracking-tight text-brand-700">{{ $votesCast }}</span>
                <span class="mt-1 text-sm text-slate-500">
                    vote(s) exprimé(s)@if ($eligibleCount > 0) sur {{ $eligibleCount }} entreprise(s) éligible(s)@endif
                </span>
            </div>
        @else
            <h2 class="font-serif text-3xl font-semibold text-brand-800">Résultats bientôt disponibles</h2>
            <p class="mt-3 text-slate-600">
                Les résultats de l’Assemblée Générale seront publiés ici dès la clôture du scrutin.
            </p>
        @endif

        <p class="mt-8 text-xs text-slate-400">Cette page se rafraîchit automatiquement.</p>
    </div>
@endsection
