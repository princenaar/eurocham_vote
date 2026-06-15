@extends('layouts.admin')

@section('title', 'Résultats')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <p class="text-sm text-slate-600">
                {{ $election->name }} · {{ $votesCast }} vote(s) exprimé(s).
                @unless ($canExportFinalResults)
                    <span class="text-amber-700">Affichage admin provisoire — exports définitifs indisponibles.</span>
                @endunless
                @if ($election->mode === \App\Models\Election::MODE_AUTO)
                    <span class="text-amber-700">Mode B : tous les candidats sont élus automatiquement.</span>
                @endif
            </p>
            @if ($elections->count() > 1)
                <select onchange="window.location = this.value"
                        class="mt-2 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                    @foreach ($elections as $option)
                        <option value="{{ route('admin.results.index', ['election' => $option->id]) }}" @selected($option->id === $election->id)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
        <div class="flex gap-2">
            @if ($canExportFinalResults)
                <a href="{{ route('admin.results.excel', ['election' => $election->id]) }}" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Export Excel</a>
                <a href="{{ route('admin.results.pdf', ['election' => $election->id]) }}" class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">Export PDF</a>
            @else
                <span class="rounded-md bg-slate-100 px-3 py-2 text-sm font-medium text-slate-500">Exports verrouillés</span>
            @endif
        </div>
    </div>

    @if ($election->isBoardVote() && $hasUnresolvedTie && $pendingTie)
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
                    <input type="hidden" name="election_id" value="{{ $election->id }}">
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

    @if ($election->isQuestionsVote())
        <div class="space-y-4">
            @foreach ($questionResults as $row)
                <div class="rounded-lg border border-slate-200 bg-white p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-serif text-base font-semibold text-brand-800">{{ $row['question']->title }}</h3>
                            @if ($row['question']->description)
                                <p class="mt-1 text-sm text-slate-600">{{ $row['question']->description }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-sm font-semibold text-brand-800">
                            Résultat : {{ $row['result'] }}
                        </span>
                    </div>
                    <dl class="mt-4 grid gap-3 sm:grid-cols-4 text-sm">
                        <div><dt class="text-slate-500">Oui</dt><dd class="font-semibold">{{ $row['yes'] }} ({{ $row['yes_percent'] }}%)</dd></div>
                        <div><dt class="text-slate-500">Non</dt><dd class="font-semibold">{{ $row['no'] }} ({{ $row['no_percent'] }}%)</dd></div>
                        <div><dt class="text-slate-500">Abstention</dt><dd class="font-semibold">{{ $row['abstain'] }}</dd></div>
                        <div><dt class="text-slate-500">Exprimés</dt><dd class="font-semibold">{{ $row['expressed'] }}</dd></div>
                    </dl>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-2 font-medium">Rang</th>
                        <th class="px-4 py-2 font-medium">Candidat</th>
                        <th class="px-4 py-2 font-medium">Structure</th>
                        <th class="px-4 py-2 font-medium text-right">Voix</th>
                        <th class="px-4 py-2 font-medium">Élu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ranking as $row)
                        <tr class="{{ in_array($row['candidate']->id, $electedIds, true) ? 'bg-gold-100/50' : '' }}">
                            <td class="px-4 py-2 text-slate-500">{{ $row['rank'] }}</td>
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $row['candidate']->displayPhotoUrl() }}"
                                         alt="{{ $row['candidate']->photo_path ? 'Photo de '.$row['candidate']->name : 'Image par défaut pour '.$row['candidate']->name }}"
                                         class="h-9 w-9 shrink-0 rounded object-cover">
                                    <span class="font-medium text-slate-900">{{ $row['candidate']->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-slate-600">{{ $row['candidate']->assemblyCompany?->name }}</td>
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
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Aucun candidat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

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
                        <th class="px-4 py-2 font-medium">Structure</th>
                        <th class="px-4 py-2 font-medium text-right">Voix</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($runoffRanking as $row)
                        <tr>
                            <td class="px-4 py-2 text-slate-500">{{ $row['rank'] }}</td>
                            <td class="px-4 py-2 font-medium text-slate-900">{{ $row['candidate']->name }}</td>
                            <td class="px-4 py-2 text-slate-600">{{ $row['candidate']->assemblyCompany?->name }}</td>
                            <td class="px-4 py-2 text-right font-semibold">{{ $row['votes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
