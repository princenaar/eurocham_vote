@extends('layouts.admin')

@section('title', 'Entreprises membres')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-slate-600">{{ $total }} entreprise(s) dans la liste.</p>
        @if (\App\Models\Election::current()->canEditConfiguration())
            <a href="{{ route('admin.companies.import') }}"
               class="rounded-md bg-brand-800 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700">
                Importer une liste (Excel/CSV)
            </a>
        @else
            <span class="rounded-md bg-slate-100 px-3 py-2 text-sm font-medium text-slate-500">
                Liste verrouillée
            </span>
        @endif
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Entreprise</th>
                    <th class="px-4 py-2 font-medium">Enquête 2025</th>
                    <th class="px-4 py-2 font-medium">Cotisation 2025</th>
                    <th class="px-4 py-2 font-medium">Nouveau membre 2026</th>
                    <th class="px-4 py-2 font-medium">Éligible</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($companies as $company)
                    <tr>
                        <td class="px-4 py-2 font-medium text-slate-900">{{ $company->name }}</td>
                        <td class="px-4 py-2">{{ $company->survey_2025 ? '✓' : '—' }}</td>
                        <td class="px-4 py-2">{{ $company->dues_2025 ? '✓' : '—' }}</td>
                        <td class="px-4 py-2">{{ $company->new_member_2026 ? '✓' : '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($company->isEligible())
                                <span class="text-emerald-600 font-medium">Oui</span>
                            @else
                                <span class="text-red-600">Non</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">
                        Aucune entreprise. Importez la liste des membres pour commencer.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $companies->links() }}</div>
@endsection
