<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'EUROCHAM Vote'))</title>

    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-brand-50 text-slate-800 antialiased">
    <div class="min-h-full flex flex-col">
        <x-tricolor-bar />
        <header class="bg-white border-b border-slate-200">
            <div class="mx-auto max-w-5xl px-4 py-3 flex items-center justify-between gap-4">
                <a href="{{ url('/') }}" class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('images/logo-eurocham.png') }}" alt="EUROCHAM Sénégal"
                         class="h-11 w-auto shrink-0">
                    <span class="hidden sm:flex flex-col leading-tight border-l border-slate-200 pl-3">
                        <span class="text-sm font-semibold tracking-tight text-brand-800">Vote électronique</span>
                        <span class="text-xs text-slate-500">Assemblée Générale 2026</span>
                    </span>
                </a>
                @yield('header-actions')
            </div>
        </header>

        <main class="flex-1">
            <div class="mx-auto max-w-5xl px-4 py-8">
                @if (session('status'))
                    <div class="mb-6 rounded-md bg-brand-50 px-4 py-3 text-sm text-brand-800 border border-brand-100">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 border border-red-200">
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

        <footer class="bg-brand-950 text-slate-300">
            <x-tricolor-bar />
            <div class="mx-auto max-w-5xl px-4 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-eurocham.png') }}" alt="EUROCHAM"
                         class="h-9 w-auto bg-white rounded px-1.5 py-1">
                    <span class="text-xs text-slate-400">Chambre des Investisseurs Européens au Sénégal</span>
                </div>
                <p class="text-xs text-slate-400">
                    Assemblée Générale du 18 juin 2026 · Réf. P01.EUROCHAM.2026
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
