@extends('layouts.admin')

@section('title', 'Importer la liste des membres')

@section('content')
    <div class="max-w-xl bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('admin.companies.import.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="file" class="block text-sm font-medium text-slate-700 mb-1">Fichier Excel ou CSV</label>
                <input id="file" name="file" type="file" accept=".xlsx,.xls,.csv,.txt" required
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-800 file:px-3 file:py-2 file:text-sm file:text-white">
            </div>

            <div class="rounded-md bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600">
                <p class="font-medium text-slate-700 mb-1">Colonnes attendues (1ʳᵉ ligne = en-têtes) :</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li><code>Nom</code> (ou Entreprise / Société) — obligatoire</li>
                    <li><code>Enquête 2025</code> — Oui/Non, X, 1/0</li>
                    <li><code>Cotisation 2025</code> — Oui/Non, X, 1/0</li>
                    <li><code>Nouveau membre 2026</code> — Oui/Non, X, 1/0</li>
                </ul>
                <p class="mt-2">
                    Une entreprise est éligible si la cotisation 2025 et l’enquête 2025 sont à jour,
                    ou si elle est marquée comme nouveau membre 2026. Les imports répétés mettent à jour
                    les entreprises existantes (par nom).
                </p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-md bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    Importer
                </button>
                <a href="{{ route('admin.companies.index') }}" class="px-4 py-2 text-sm text-slate-600">Annuler</a>
            </div>
        </form>
    </div>
@endsection
