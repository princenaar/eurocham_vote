@extends('layouts.admin')

@section('title', 'Traçabilité')

@section('content')
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        La traçabilité est réservée aux administrateurs. Les journaux ne listent pas les candidats
        choisis par une entreprise ; le secret repose sur le contrôle d’accès applicatif et opérationnel.
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Date</th>
                    <th class="px-4 py-2 font-medium">Action</th>
                    <th class="px-4 py-2 font-medium">Description</th>
                    <th class="px-4 py-2 font-medium">Admin</th>
                    <th class="px-4 py-2 font-medium">IP</th>
                    <th class="px-4 py-2 font-medium">Contexte</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-2 text-slate-500">
                            {{ $log->created_at?->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-4 py-2 font-mono text-xs text-slate-700">{{ $log->action }}</td>
                        <td class="px-4 py-2 text-slate-800">{{ $log->description ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $log->user?->email ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $log->ip_address ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($log->context)
                                <pre class="max-w-xs overflow-auto rounded bg-slate-50 p-2 text-xs text-slate-600">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-400">Aucun événement enregistré.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
