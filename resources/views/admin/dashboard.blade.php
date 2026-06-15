@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    @php
        $modeLabel = match ($election->mode) {
            \App\Models\Election::MODE_SELECT => 'Mode A — sélection de '
                . $election->candidate_min_choices . ' à ' . $election->candidate_max_choices,
            \App\Models\Election::MODE_AUTO => 'Mode B — élection automatique',
            default => 'Non déterminé (aucun candidat)',
        };
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white rounded-lg border border-slate-200 border-t-2 border-t-brand-800 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Entreprises</div>
            <div class="mt-1 font-serif text-3xl font-semibold text-brand-800">{{ $companyCount }}</div>
            <div class="text-xs text-slate-400">dont {{ $eligibleCount }} éligibles</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 border-t-2 border-t-brand-800 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Candidats</div>
            <div class="mt-1 font-serif text-3xl font-semibold text-brand-800">{{ $candidateCount }}</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 border-t-2 border-t-brand-800 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Votes tour principal</div>
            <div class="mt-1 font-serif text-3xl font-semibold text-brand-800">{{ $votesCast }}</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 border-t-2 border-t-brand-800 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Participation principale</div>
            <div class="mt-1 font-serif text-3xl font-semibold text-brand-800">{{ $participation }}%</div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-lg border border-slate-200 p-5">
        <h2 class="font-serif text-base font-semibold text-brand-800">État du scrutin</h2>
        <dl class="mt-3 grid gap-3 sm:grid-cols-3 text-sm">
            <div>
                <dt class="text-slate-500">État</dt>
                <dd class="font-medium text-slate-700">{{ $election->statusLabel() }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Fenêtre de vote</dt>
                <dd class="font-medium {{ $election->window_open ? 'text-emerald-600' : 'text-slate-700' }}">
                    {{ $election->window_open ? 'Ouverte' : 'Fermée' }}
                </dd>
            </div>
            <div>
                <dt class="text-slate-500">QR code</dt>
                <dd class="font-medium {{ $election->qr_active ? 'text-emerald-600' : 'text-slate-700' }}">
                    {{ $election->qr_active ? 'Actif' : 'Inactif' }}
                </dd>
            </div>
            <div>
                <dt class="text-slate-500">Mode de scrutin</dt>
                <dd class="font-medium text-slate-700">{{ $modeLabel }}</dd>
            </div>
        </dl>
        @if ($election->isRunoff())
            <div class="mt-4 rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                Vote exceptionnel de départage : {{ $currentRoundVotesCast }} vote(s) au tour {{ $election->current_round }}
                ({{ $currentRoundParticipation }}% de participation sur ce tour).
            </div>
        @endif
        <a href="{{ route('admin.election.edit') }}" class="mt-4 inline-block text-sm font-medium text-brand-700 underline">
            Gérer le scrutin & le QR code →
        </a>
    </div>
@endsection
