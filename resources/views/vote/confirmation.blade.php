@extends('layouts.app')

@section('title', 'Vote enregistré — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-lg mx-auto text-center py-12" data-testid="vote-confirmation">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-brand-800 ring-4 ring-brand-100">
            <svg class="h-9 w-9 text-gold-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>

        <h2 class="font-serif text-3xl font-semibold text-brand-800">Votre vote a été enregistré</h2>
        <p class="mt-3 text-slate-600">
            Votre vote est <strong>définitif et irrévocable</strong>. Conservez le numéro de
            référence ci-dessous comme preuve de votre participation.
        </p>

        <div class="mt-6 rounded-lg border-2 border-dashed border-gold-300 bg-gold-100 px-6 py-5">
            <div class="text-xs font-medium uppercase tracking-wide text-gold-700">Numéro de référence</div>
            <div class="mt-1 font-mono text-xl font-bold tracking-tight text-brand-900" data-testid="vote-reference">{{ $reference }}</div>
        </div>

        <p class="mt-8 text-sm text-slate-400">
            Merci de votre participation à l’Assemblée Générale EUROCHAM Sénégal 2026.
        </p>
    </div>
@endsection
