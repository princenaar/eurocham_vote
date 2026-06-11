@extends('layouts.app')

@section('title', 'Bulletin de vote — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-3xl mx-auto py-8">
        <div>
            <h2 class="font-serif text-3xl font-semibold text-brand-800">{{ $election->name }}</h2>
            <p class="mt-1 text-sm text-slate-600">
                Entreprise : <span class="font-medium text-slate-800">{{ $company->name }}</span>
                @if ($isProxy)
                    · <span class="font-medium text-slate-800">Vote par procuration</span>
                @endif
            </p>
        </div>

        <div class="mt-3 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            Pour chaque question, choisissez Oui, Non ou Abstention. Le serveur vérifiera que toutes les questions ont une réponse.
        </div>

        <form method="POST" action="{{ route('vote.review') }}" class="mt-6 space-y-4">
            @csrf
            @foreach ($questions as $question)
                <fieldset class="rounded-lg border border-slate-200 bg-white p-5">
                    <legend class="font-serif text-lg font-semibold text-brand-800">{{ $question->title }}</legend>
                    @if ($question->description)
                        <p class="mt-2 text-sm text-slate-600">{{ $question->description }}</p>
                    @endif
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        @foreach (['yes' => 'Oui', 'no' => 'Non', 'abstain' => 'Abstention'] as $value => $label)
                            <label class="flex cursor-pointer items-center gap-3 rounded-md border border-slate-200 px-4 py-3 text-sm hover:bg-brand-50">
                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $value }}" required
                                       class="h-4 w-4 border-slate-300 text-brand-700 focus:ring-brand-600">
                                <span class="font-medium text-slate-800">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error("answers.{$question->id}")<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </fieldset>
            @endforeach

            <button type="submit"
                    class="w-full rounded-md bg-brand-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2">
                Vérifier mon vote
            </button>
        </form>
    </div>
@endsection
