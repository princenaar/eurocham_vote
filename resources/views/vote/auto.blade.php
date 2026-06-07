@extends('layouts.app')

@section('title', 'Résultat — Élection automatique')

@section('content')
    <div class="max-w-2xl mx-auto py-8">
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800">
            <h2 class="text-lg font-semibold">Élection automatique</h2>
            <p class="mt-1 text-sm">
                Le nombre de candidats étant inférieur ou égal au nombre de sièges à pourvoir
                ({{ $election->candidate_threshold }}), l’ensemble des candidats est
                <strong>élu automatiquement</strong>. Aucun vote n’est requis.
            </p>
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-3">
                <h3 class="text-sm font-semibold text-slate-900">
                    Membres élus du Conseil d’Administration 2026
                </h3>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($candidates as $candidate)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">
                            {{ $loop->iteration }}
                        </span>
                        <span class="text-slate-800">{{ $candidate->name }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="mt-6 text-center text-sm text-slate-400">
            Un problème ? Contactez le secrétariat EUROCHAM.
        </p>
    </div>
@endsection
