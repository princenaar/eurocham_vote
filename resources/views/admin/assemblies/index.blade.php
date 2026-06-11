@extends('layouts.admin')

@section('title', 'AG & Votes')

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="font-serif text-base font-semibold text-brand-800">Créer une AG</h2>
            <form method="POST" action="{{ route('admin.assemblies.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nom</label>
                    <input name="name" type="text" required value="{{ old('name') }}"
                           class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Référence</label>
                    <input name="reference" type="text" required value="{{ old('reference') }}"
                           class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Date</label>
                    <input name="held_on" type="date" value="{{ old('held_on') }}"
                           class="mt-1 w-48 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                </div>
                <button class="rounded-md bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    Créer l’AG
                </button>
            </form>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="font-serif text-base font-semibold text-brand-800">Créer un vote</h2>
            @if ($assemblies->isEmpty())
                <p class="mt-3 text-sm text-slate-500">Créez d’abord une AG.</p>
            @else
                <form method="POST" action="{{ route('admin.assemblies.votes.store', $assemblies->first()) }}" class="mt-4 space-y-4"
                      x-data="{ assembly: '{{ $assemblies->first()->id }}' }"
                      x-bind:action="'{{ url('/admin/assemblies') }}/' + assembly + '/votes'">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700">AG</label>
                        <select x-model="assembly" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                            @foreach ($assemblies as $assembly)
                                <option value="{{ $assembly->id }}">{{ $assembly->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nom du vote</label>
                        <input name="name" type="text" required value="{{ old('name') }}"
                               class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Type</label>
                        <select name="type" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                            <option value="{{ \App\Models\Election::TYPE_BOARD }}">Élection du Conseil d’Administration</option>
                            <option value="{{ \App\Models\Election::TYPE_QUESTIONS }}">Questions Oui / Non / Abstention</option>
                        </select>
                    </div>
                    <button class="rounded-md bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                        Créer le vote
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="mt-6 space-y-4">
        @foreach ($assemblies as $assembly)
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-serif text-lg font-semibold text-brand-800">{{ $assembly->name }}</h2>
                        <p class="text-sm text-slate-500">
                            {{ $assembly->reference }}
                            @if ($assembly->held_on)
                                · {{ $assembly->held_on->format('d/m/Y') }}
                            @endif
                            · {{ $assembly->companies()->count() }} entreprise(s)
                        </p>
                    </div>
                    <a href="{{ route('admin.companies.index', ['assembly' => $assembly->id]) }}"
                       class="rounded-md border border-brand-200 px-3 py-2 text-sm font-medium text-brand-800 hover:bg-brand-50">
                        Entreprises
                    </a>
                </div>
                <div class="mt-4 overflow-hidden rounded-md border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-2 font-medium">Vote</th>
                                <th class="px-4 py-2 font-medium">Type</th>
                                <th class="px-4 py-2 font-medium">État</th>
                                <th class="px-4 py-2 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($assembly->elections as $election)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-slate-900">{{ $election->name }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ $election->typeLabel() }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ $election->statusLabel() }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('admin.election.edit', ['election' => $election->id]) }}" class="text-brand-700 underline">Gérer</a>
                                        <span class="mx-1 text-slate-300">·</span>
                                        <a href="{{ route('admin.results.index', ['election' => $election->id]) }}" class="text-brand-700 underline">Résultats</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-5 text-center text-slate-400">Aucun vote.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endsection
