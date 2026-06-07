@extends('layouts.app')

@section('title', 'Vote enregistré — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-lg mx-auto text-center py-12">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
            <svg class="h-9 w-9 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>

        <h2 class="text-2xl font-semibold text-slate-900">Votre vote a été enregistré</h2>
        <p class="mt-3 text-slate-600">
            Votre vote est <strong>définitif et irrévocable</strong>. Conservez le numéro de
            référence ci-dessous comme preuve de votre participation.
        </p>

        <div class="mt-6 rounded-lg border-2 border-dashed border-emerald-300 bg-emerald-50 px-6 py-5">
            <div class="text-xs font-medium uppercase tracking-wide text-emerald-600">Numéro de référence</div>
            <div class="mt-1 font-mono text-xl font-bold tracking-tight text-emerald-900">{{ $reference }}</div>
        </div>

        <p class="mt-8 text-sm text-slate-400">
            Merci de votre participation à l’Assemblée Générale EUROCHAM Sénégal 2026.
        </p>
    </div>
@endsection
