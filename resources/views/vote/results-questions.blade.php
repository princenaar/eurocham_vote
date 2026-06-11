@extends('layouts.app')

@section('title', 'Résultats — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-4xl mx-auto py-8">
        <div class="text-center">
            <h2 class="font-serif text-4xl font-semibold tracking-tight text-brand-800">Résultats du vote</h2>
            <p class="mt-2 text-slate-600">{{ $election->name }} · {{ $votesCast }} bulletin(s)</p>
        </div>

        <div class="mt-6 space-y-4">
            @foreach ($questionResults as $row)
                <div class="rounded-lg border border-slate-200 bg-white p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-serif text-lg font-semibold text-brand-800">{{ $row['question']->title }}</h3>
                            @if ($row['question']->description)
                                <p class="mt-1 text-sm text-slate-600">{{ $row['question']->description }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-sm font-semibold text-brand-800">
                            Résultat : {{ $row['result'] }}
                        </span>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-md bg-emerald-50 px-4 py-3">
                            <div class="text-xs uppercase text-emerald-700">Oui</div>
                            <div class="mt-1 text-2xl font-semibold text-emerald-800">{{ $row['yes'] }}</div>
                            <div class="text-sm text-emerald-700">{{ $row['yes_percent'] }}% des exprimés</div>
                        </div>
                        <div class="rounded-md bg-red-50 px-4 py-3">
                            <div class="text-xs uppercase text-red-700">Non</div>
                            <div class="mt-1 text-2xl font-semibold text-red-800">{{ $row['no'] }}</div>
                            <div class="text-sm text-red-700">{{ $row['no_percent'] }}% des exprimés</div>
                        </div>
                        <div class="rounded-md bg-slate-50 px-4 py-3">
                            <div class="text-xs uppercase text-slate-500">Abstention</div>
                            <div class="mt-1 text-2xl font-semibold text-slate-800">{{ $row['abstain'] }}</div>
                            <div class="text-sm text-slate-500">Hors résultat gagnant</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
