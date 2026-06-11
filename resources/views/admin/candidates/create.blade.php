@extends('layouts.admin')

@section('title', 'Ajouter un candidat')

@section('content')
    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('admin.candidates.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="election_id" value="{{ $election->id }}">
            @include('admin.candidates._form', ['candidate' => null])
            <div class="flex gap-3">
                <button type="submit" class="rounded-md bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    Enregistrer
                </button>
                <a href="{{ route('admin.candidates.index', ['election' => $election->id]) }}" class="px-4 py-2 text-sm text-slate-600">Annuler</a>
            </div>
        </form>
    </div>
@endsection
