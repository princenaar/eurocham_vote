@extends('layouts.app')

@section('title', 'Résultats — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-3xl mx-auto py-8">
        <div class="text-center">
            <h2 class="font-serif text-4xl font-semibold tracking-tight text-brand-800">Résultats du scrutin</h2>
            <p class="mt-2 text-slate-600">
                {{ $election->name }}
                @if ($election->mode === \App\Models\Election::MODE_AUTO)
                    · Élection automatique
                @else
                    · {{ $votesCast }} vote(s) exprimé(s)
                @endif
            </p>
        </div>

        @if ($hasUnresolvedTie && $pendingTie)
            <div class="mt-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-center text-sm text-amber-800">
                <strong>Égalité pour {{ $pendingTie['seats'] }} siège(s)</strong> entre
                {{ $pendingTie['tied']->pluck('name')->implode(', ') }}.
                Un vote de départage est nécessaire.
            </div>
        @endif

        {{-- Elected Board --}}
        <div class="mt-6 rounded-lg border border-brand-100 bg-white overflow-hidden shadow-sm">
            <div class="flex items-center gap-3 border-b border-brand-900 bg-brand-800 px-5 py-3">
                <span class="flex h-4 w-1 flex-col overflow-hidden rounded" aria-hidden="true">
                    <span class="flex-1 bg-sng-green"></span>
                    <span class="flex-1 bg-sng-gold"></span>
                    <span class="flex-1 bg-sng-red"></span>
                </span>
                <h3 class="font-serif text-base font-semibold text-white">
                    Conseil d’Administration 2026 — <span class="text-gold-300">{{ $electedBoard->count() }} élu(s)</span>
                </h3>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($electedBoard as $candidate)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-800 text-xs font-semibold text-gold-300">
                            {{ $loop->iteration }}
                        </span>
                        <span class="font-medium text-slate-800">{{ $candidate->name }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-slate-400">Conseil non encore déterminé.</li>
                @endforelse
            </ul>
        </div>

        {{-- Full ranking (Mode A) --}}
        @if ($ranking)
            <h3 class="mt-8 mb-3 font-serif text-lg font-semibold text-brand-800">Détail des voix</h3>
            <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-2 font-medium">Rang</th>
                            <th class="px-4 py-2 font-medium">Candidat</th>
                            <th class="px-4 py-2 font-medium text-right">Voix</th>
                            <th class="px-4 py-2 font-medium">Élu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($ranking as $row)
                            <tr class="{{ in_array($row['candidate']->id, $electedIds, true) ? 'bg-gold-100/50' : '' }}">
                                <td class="px-4 py-2 text-slate-500">{{ $row['rank'] }}</td>
                                <td class="px-4 py-2 font-medium text-slate-900">{{ $row['candidate']->name }}</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ $row['votes'] }}</td>
                                <td class="px-4 py-2">
                                    @if (in_array($row['candidate']->id, $electedIds, true))
                                        <span class="inline-flex items-center rounded-full bg-gold-100 px-2 py-0.5 text-xs font-medium text-gold-700">Élu</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($isRunoff && $runoffRanking)
            <h3 class="mt-8 mb-3 font-serif text-lg font-semibold text-brand-800">
                Vote de départage — tour {{ $election->current_round }}
            </h3>
            <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-2 font-medium">Rang</th>
                            <th class="px-4 py-2 font-medium">Candidat</th>
                            <th class="px-4 py-2 font-medium text-right">Voix</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($runoffRanking as $row)
                            <tr>
                                <td class="px-4 py-2 text-slate-500">{{ $row['rank'] }}</td>
                                <td class="px-4 py-2 font-medium text-slate-900">{{ $row['candidate']->name }}</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ $row['votes'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
