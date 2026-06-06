@extends('layouts.admin')

@section('title', 'Résultats')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-slate-600">
            {{ $votesCast }} vote(s) exprimé(s).
            @if ($election->mode === \App\Models\Election::MODE_AUTO)
                <span class="text-amber-700">Mode B : tous les candidats sont élus automatiquement.</span>
            @endif
        </p>
        <div class="flex gap-2">
            <a href="{{ route('admin.results.excel') }}" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Export Excel</a>
            <a href="{{ route('admin.results.pdf') }}" class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">Export PDF</a>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Rang</th>
                    <th class="px-4 py-2 font-medium">Candidat</th>
                    <th class="px-4 py-2 font-medium text-right">Voix</th>
                    <th class="px-4 py-2 font-medium">Élu auto.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($results as $i => $candidate)
                    <tr>
                        <td class="px-4 py-2 text-slate-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-2 font-medium text-slate-900">{{ $candidate->name }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ $candidate->selections_count }}</td>
                        <td class="px-4 py-2">{{ $candidate->auto_elected ? 'Oui' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-400">Aucun candidat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
