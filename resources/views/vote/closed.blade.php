@extends('layouts.app')

@section('title', 'Vote — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-lg mx-auto text-center py-12">
        <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
            <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <h2 class="font-serif text-3xl font-semibold text-brand-800">Le scrutin n’est pas ouvert</h2>
        <p class="mt-3 text-slate-600">
            L’espace de vote n’est accessible que pendant la fenêtre officielle du scrutin,
            le 18 juin 2026. Veuillez scanner le QR code affiché en salle le moment venu.
        </p>
        <p class="mt-6 text-sm text-slate-400">
            Un problème ? Contactez le secrétariat EUROCHAM.
        </p>
    </div>
@endsection
