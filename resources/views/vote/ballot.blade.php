@extends('layouts.app')

@section('title', 'Bulletin de vote — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-2xl mx-auto py-8" x-data="ballot({{ $required }})" data-testid="board-ballot">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-serif text-3xl font-semibold text-brand-800">Bulletin de vote</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Entreprise : <span class="font-medium text-slate-800">{{ $company->name }}</span>
                    @if ($isProxy)
                        · <span class="font-medium text-slate-800">Vote par procuration</span>
                    @endif
                </p>
            </div>
        </div>

        @if ($isRunoff)
            <div class="mt-3 rounded-md bg-indigo-50 border border-indigo-200 px-4 py-3 text-sm text-indigo-800">
                <strong>Vote de départage.</strong> Ce tour départage les candidats à égalité
                pour {{ $required }} siège(s) restant(s).
            </div>
        @endif

        <div class="mt-3 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            Sélectionnez <strong>exactement {{ $required }}</strong> candidat(s). Le bouton de
            validation s’active uniquement lorsque {{ $required }} candidats sont cochés.
            @if ($isProxy)
                <br>Ce bulletin est enregistré comme vote par procuration pour l’entreprise sélectionnée.
            @endif
        </div>

        {{-- Sticky live counter (UX only — the server re-checks the exact count). --}}
        <div class="sticky top-0 z-10 mt-4 flex items-center justify-between rounded-md border border-slate-200 bg-white px-4 py-3 shadow-sm" data-testid="selection-counter">
            <span class="text-sm text-slate-600">Sélectionnés</span>
            <span class="font-mono text-sm font-semibold"
                  :class="count === required ? 'text-brand-700' : 'text-slate-900'">
                <span x-text="count"></span> / {{ $required }}
            </span>
        </div>

        <form method="POST" action="{{ route('vote.review') }}" class="mt-4">
            @csrf

            <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
                @foreach ($candidates as $candidate)
                    <li>
                        <label class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-slate-50"
                               :class="isChecked({{ $candidate->id }}) ? 'bg-brand-50' : ''">
                            <input
                                type="checkbox"
                                name="candidates[]"
                                value="{{ $candidate->id }}"
                                @change="toggle($event)"
                                :disabled="count >= required && !isChecked({{ $candidate->id }})"
                                class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-600 disabled:opacity-40"
                                data-testid="candidate-checkbox"
                            >
                            @if ($candidate->photo_path)
                                <img src="{{ $candidate->photoUrl() }}" alt="Photo de {{ $candidate->name }}" class="h-12 w-12 rounded object-cover" data-testid="candidate-avatar">
                            @else
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded bg-slate-100 text-sm font-semibold text-slate-500">
                                    {{ mb_substr($candidate->name, 0, 1) }}
                                </span>
                            @endif
                            <span class="min-w-0">
                                <span class="block font-medium text-slate-800">{{ $candidate->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $candidate->assemblyCompany?->name }}</span>
                            </span>
                        </label>
                    </li>
                @endforeach
            </ul>

            <button
                type="submit"
                data-testid="board-review-submit"
                :disabled="count !== required"
                class="mt-6 w-full rounded-md bg-brand-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"
            >
                <span x-show="count === required">Vérifier mon vote</span>
                <span x-show="count !== required" x-cloak>
                    Sélectionnez <span x-text="required - count"></span> candidat(s) de plus
                </span>
            </button>
        </form>
    </div>

    <script>
        function ballot(required) {
            return {
                required,
                selected: new Set(),
                get count() { return this.selected.size; },
                isChecked(id) { return this.selected.has(id); },
                toggle(event) {
                    const id = parseInt(event.target.value, 10);
                    if (event.target.checked) {
                        if (this.selected.size >= this.required) {
                            event.target.checked = false;
                            return;
                        }
                        this.selected.add(id);
                    } else {
                        this.selected.delete(id);
                    }
                },
            };
        }
    </script>
@endsection
