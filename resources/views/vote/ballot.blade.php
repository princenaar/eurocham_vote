@extends('layouts.app')

@section('title', 'Bulletin de vote — EUROCHAM AG 2026')

@section('content')
    <div class="max-w-2xl mx-auto py-8" x-data="ballot({{ $required }})">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Bulletin de vote</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Entreprise : <span class="font-medium text-slate-800">{{ $company->name }}</span>
                    @if ($proxyCompanyName)
                        · Procuration : <span class="font-medium text-slate-800">{{ $proxyCompanyName }}</span>
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
        </div>

        {{-- Sticky live counter (UX only — the server re-checks the exact count). --}}
        <div class="sticky top-0 z-10 mt-4 flex items-center justify-between rounded-md border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <span class="text-sm text-slate-600">Sélectionnés</span>
            <span class="font-mono text-sm font-semibold"
                  :class="count === required ? 'text-emerald-600' : 'text-slate-900'">
                <span x-text="count"></span> / {{ $required }}
            </span>
        </div>

        <form method="POST" action="{{ route('vote.review') }}" class="mt-4">
            @csrf

            <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
                @foreach ($candidates as $candidate)
                    <li>
                        <label class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-slate-50"
                               :class="isChecked({{ $candidate->id }}) ? 'bg-emerald-50' : ''">
                            <input
                                type="checkbox"
                                name="candidates[]"
                                value="{{ $candidate->id }}"
                                @change="toggle($event)"
                                :disabled="count >= required && !isChecked({{ $candidate->id }})"
                                class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 disabled:opacity-40"
                            >
                            <span class="text-slate-800">{{ $candidate->name }}</span>
                        </label>
                    </li>
                @endforeach
            </ul>

            <button
                type="submit"
                :disabled="count !== required"
                class="mt-6 w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"
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
