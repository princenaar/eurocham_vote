@extends('layouts.admin')

@section('title', 'QR code de vote')

@section('content')
    <div class="min-h-[70vh] bg-white rounded-lg border border-slate-200 px-6 py-8 text-center">
        <div class="mx-auto max-w-3xl">
            <h2 class="font-serif text-3xl font-semibold text-brand-800">QR code de vote</h2>
            <div class="mt-8 flex justify-center">
                <img src="{{ route('admin.election.qr') }}" alt="QR code de vote" class="h-[min(70vw,34rem)] w-[min(70vw,34rem)]">
            </div>
            <p class="mt-6 break-all font-mono text-base text-slate-700">{{ $voteUrl }}</p>
            <a href="{{ route('admin.election.edit') }}"
               class="mt-8 inline-flex rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                Retour au scrutin & QR
            </a>
        </div>
    </div>
@endsection
