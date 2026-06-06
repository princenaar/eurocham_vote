@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    @php
        $modeLabel = match ($election->mode) {
            \App\Models\Election::MODE_SELECT => 'Mode A — sélection de ' . $election->candidate_threshold,
            \App\Models\Election::MODE_AUTO => 'Mode B — élection automatique',
            default => 'Non déterminé (aucun candidat)',
        };
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Entreprises</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $companyCount }}</div>
            <div class="text-xs text-slate-400">dont {{ $eligibleCount }} éligibles</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Candidats</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $candidateCount }}</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Votes exprimés</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $votesCast }}</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Participation</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $participation }}%</div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-lg border border-slate-200 p-5">
        <h2 class="text-sm font-semibold text-slate-900">État du scrutin</h2>
        <dl class="mt-3 grid gap-3 sm:grid-cols-3 text-sm">
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
        <a href="{{ route('admin.election.edit') }}" class="mt-4 inline-block text-sm font-medium text-slate-900 underline">
            Gérer le scrutin & le QR code →
        </a>
    </div>
@endsection
