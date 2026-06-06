@extends('layouts.admin')

@section('title', 'Candidats')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-slate-600">
            {{ $candidates->count() }} candidat(s).
            @if ($election->mode === \App\Models\Election::MODE_AUTO)
                <span class="text-amber-700">Mode B : tous élus automatiquement (≤ {{ $election->candidate_threshold }}).</span>
            @elseif ($election->mode === \App\Models\Election::MODE_SELECT)
                <span class="text-slate-700">Mode A : les votants sélectionnent {{ $election->candidate_threshold }} candidats.</span>
            @endif
        </p>
        <a href="{{ route('admin.candidates.create') }}"
           class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
            + Ajouter un candidat
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Ordre</th>
                    <th class="px-4 py-2 font-medium">Nom</th>
                    <th class="px-4 py-2 font-medium">Élu auto.</th>
                    <th class="px-4 py-2 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($candidates as $candidate)
                    <tr>
                        <td class="px-4 py-2 text-slate-500">{{ $candidate->display_order }}</td>
                        <td class="px-4 py-2 font-medium text-slate-900">{{ $candidate->name }}</td>
                        <td class="px-4 py-2">{{ $candidate->auto_elected ? 'Oui' : '—' }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.candidates.edit', $candidate) }}" class="text-slate-700 underline">Modifier</a>
                            <form method="POST" action="{{ route('admin.candidates.destroy', $candidate) }}" class="inline"
                                  onsubmit="return confirm('Supprimer ce candidat ?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-red-600 underline">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-400">Aucun candidat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
