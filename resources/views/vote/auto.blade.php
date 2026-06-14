@extends('layouts.app')

@section('title', 'Résultat — Élection automatique')

@section('content')
    <div class="max-w-2xl mx-auto py-8" data-testid="board-auto">
        <div class="rounded-lg border border-brand-100 bg-brand-50 px-5 py-4 text-brand-800">
            <h2 class="font-serif text-xl font-semibold">Élection automatique</h2>
            <p class="mt-1 text-sm">
                Le nombre de candidats étant inférieur ou égal au nombre de sièges à pourvoir
                ({{ $election->candidate_threshold }}), l’ensemble des candidats est
                <strong>élu automatiquement</strong>. Aucun vote n’est requis.
            </p>
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-3">
                <h3 class="font-serif text-base font-semibold text-brand-800">
                    Membres élus du Conseil d’Administration 2026
                </h3>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($candidates as $candidate)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-800">
                            {{ $loop->iteration }}
                        </span>
                        @if ($candidate->photo_path)
                            <img src="{{ $candidate->photoUrl() }}" alt="Photo de {{ $candidate->name }}" class="h-10 w-10 rounded object-cover" data-testid="candidate-avatar">
                        @else
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-slate-100 text-xs font-semibold text-slate-500">
                                {{ mb_substr($candidate->name, 0, 1) }}
                            </span>
                        @endif
                        <span class="min-w-0">
                            <span class="block font-medium text-slate-800">{{ $candidate->name }}</span>
                            <span class="block text-xs text-slate-500">{{ $candidate->assemblyCompany?->name }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="mt-6 text-center text-sm text-slate-400">
            Un problème ? Contactez le secrétariat EUROCHAM.
        </p>
    </div>
@endsection
