@extends('layouts.admin')

@section('title', 'Résultats')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-slate-600">
            {{ $votesCast }} vote(s) exprimé(s) au tour principal.
            @unless ($canExportFinalResults)
                <span class="text-amber-700">Affichage admin provisoire — exports définitifs indisponibles.</span>
            @endunless
            @if ($election->mode === \App\Models\Election::MODE_AUTO)
                <span class="text-amber-700">Mode B : tous les candidats sont élus automatiquement.</span>
            @endif
        </p>
        <div class="flex gap-2">
            @if ($canExportFinalResults)
                <a href="{{ route('admin.results.excel') }}" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Export Excel</a>
                <a href="{{ route('admin.results.pdf') }}" class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">Export PDF</a>
            @else
                <span class="rounded-md bg-slate-100 px-3 py-2 text-sm font-medium text-slate-500">Exports verrouillés</span>
            @endif
        </div>
    </div>

    @if ($hasUnresolvedTie && $pendingTie)
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="text-sm text-amber-800">
                    <strong>Égalité à départager.</strong>
                    {{ $pendingTie['tied']->count() }} candidats à égalité pour
                    {{ $pendingTie['seats'] }} siège(s) restant(s) :
                    <span class="font-medium">{{ $pendingTie['tied']->pluck('name')->implode(', ') }}</span>.
                </div>
                <form method="POST" action="{{ route('admin.election.runoff') }}"
                      onsubmit="return confirm('Lancer un vote de départage pour {{ $pendingTie['seats'] }} siège(s) entre les candidats à égalité ? Le vote sera rouvert.');">
                    @csrf
                    <button type="submit"
                            @disabled($election->window_open)
                            class="rounded-md bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:bg-slate-300 disabled:cursor-not-allowed">
                        Lancer le départage exceptionnel
                    </button>
                </form>
            </div>
            @if ($election->window_open)
                <p class="mt-2 text-xs text-amber-700">Clôturez d’abord le vote en cours pour lancer le départage.</p>
            @endif
        </div>
    @endif

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
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
                @forelse ($ranking as $row)
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
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-400">Aucun candidat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($isRunoff && $runoffRanking)
        <h3 class="mt-8 mb-3 font-serif text-base font-semibold text-brand-800">
            Vote de départage — tour {{ $election->current_round }}
        </h3>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
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
@endsection
