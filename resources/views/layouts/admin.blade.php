<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') — EUROCHAM Vote</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
<div class="min-h-full lg:flex" x-data="{ open: false }">
    <aside class="lg:w-64 bg-slate-900 text-slate-200 lg:min-h-screen">
        <div class="px-5 py-4 flex items-center justify-between">
            <div>
                <div class="font-semibold text-white">EUROCHAM Vote</div>
                <div class="text-xs text-slate-400">Administration · AG 2026</div>
            </div>
            <button @click="open = !open" class="lg:hidden text-slate-300" aria-label="Menu">☰</button>
        </div>
        @php
            $nav = [
                ['admin.dashboard', 'Tableau de bord'],
                ['admin.companies.index', 'Entreprises'],
                ['admin.candidates.index', 'Candidats'],
                ['admin.election.edit', 'Scrutin & QR'],
                ['admin.results.index', 'Résultats'],
            ];
        @endphp
        <nav class="px-3 pb-4 lg:block" :class="open ? 'block' : 'hidden'">
            @foreach ($nav as [$route, $label])
                <a href="{{ route($route) }}"
                   class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs($route) || request()->routeIs($route.'*') ? 'bg-slate-700 text-white' : 'hover:bg-slate-800' }}">
                    {{ $label }}
                </a>
            @endforeach
            <a href="{{ route('results.public') }}" target="_blank" rel="noopener"
               class="mt-2 block rounded-md px-3 py-2 text-sm text-slate-300 hover:bg-slate-800">
                Affichage public ↗
            </a>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-4 border-t border-slate-700 pt-3">
                @csrf
                <button type="submit" class="block w-full text-left rounded-md px-3 py-2 text-sm text-slate-300 hover:bg-slate-800">
                    Déconnexion
                </button>
            </form>
        </nav>
    </aside>

    <main class="flex-1">
        <header class="bg-white border-b border-slate-200 px-6 py-4">
            <h1 class="text-lg font-semibold text-slate-900">@yield('title', 'Administration')</h1>
        </header>

        <div class="p-6 max-w-6xl">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 border border-emerald-200">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('import_errors'))
                <div class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800 border border-amber-200">
                    <p class="font-medium mb-1">Lignes ignorées lors de l’import :</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach (session('import_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 border border-red-200">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
