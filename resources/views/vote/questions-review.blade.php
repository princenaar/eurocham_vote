@extends('layouts.app')

@section('title', 'Vérification — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-3xl mx-auto py-8" data-testid="questions-review">
        <h2 class="font-serif text-3xl font-semibold text-brand-800">Vérifiez votre vote</h2>
        <p class="mt-2 text-slate-600">
            Vérifiez vos réponses ci-dessous. Après validation, votre vote sera
            <strong>définitif et irrévocable</strong>.
        </p>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-3 text-sm text-slate-600">
                Entreprise : <span class="font-medium text-slate-800">{{ $company->name }}</span>
                @if ($isProxy)
                    <br><span class="font-medium text-slate-800">Vote par procuration</span>
                @endif
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($questions as $question)
                    <div class="px-5 py-4" data-testid="reviewed-question">
                        <div class="font-medium text-slate-900">{{ $question->title }}</div>
                        <div class="mt-1 text-sm text-brand-800">{{ $labels[$answers[$question->id]] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row-reverse">
            <form method="POST" action="{{ route('vote.submit') }}" class="sm:flex-1"
                  onsubmit="this.querySelector('button').disabled = true;">
                @csrf
                @foreach ($answers as $questionId => $answer)
                    <input type="hidden" name="answers[{{ $questionId }}]" value="{{ $answer }}">
                @endforeach
                <button type="submit" data-testid="confirm-vote-submit"
                        class="w-full rounded-md bg-brand-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 disabled:bg-slate-400">
                    Confirmer définitivement mon vote
                </button>
            </form>
            <a href="{{ route('vote.ballot') }}"
               class="rounded-md border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 sm:flex-1">
                Modifier mes réponses
            </a>
        </div>
    </div>
@endsection
