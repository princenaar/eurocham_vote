@extends('layouts.app')

@section('title', 'Vérification — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-2xl mx-auto py-8">
        <h2 class="font-serif text-3xl font-semibold text-brand-800">Vérifiez votre vote</h2>
        <p class="mt-2 text-slate-600">
            Vérifiez vos choix ci-dessous. Après validation, votre vote sera
            <strong>définitif et irrévocable</strong>.
        </p>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-3 text-sm text-slate-600">
                Entreprise : <span class="font-medium text-slate-800">{{ $company->name }}</span>
                @if ($isProxy)
                    <br><span class="font-medium text-slate-800">Vote par procuration</span>
                    <br><span class="text-xs text-slate-500">Ce bulletin consomme la voix de l’entreprise sélectionnée.</span>
                @endif
            </div>
            <div class="px-5 py-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    {{ $candidates->count() }} candidat(s) sélectionné(s)
                </div>
                <ul class="mt-2 divide-y divide-slate-100">
                    @foreach ($candidates as $candidate)
                        <li class="flex items-center gap-3 py-2">
                            <svg class="h-4 w-4 text-brand-700" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span class="text-slate-800">{{ $candidate->name }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row-reverse">
            <form method="POST" action="{{ route('vote.submit') }}" class="sm:flex-1"
                  onsubmit="this.querySelector('button').disabled = true;">
                @csrf
                @foreach ($chosen as $candidateId)
                    <input type="hidden" name="candidates[]" value="{{ $candidateId }}">
                @endforeach
                <button type="submit"
                        class="w-full rounded-md bg-brand-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 disabled:bg-slate-400">
                    Confirmer définitivement mon vote
                </button>
            </form>
            <a href="{{ route('vote.ballot') }}"
               class="rounded-md border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 sm:flex-1">
                Modifier mes choix
            </a>
        </div>
    </div>
@endsection
