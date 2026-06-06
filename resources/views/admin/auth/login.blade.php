<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — EUROCHAM Vote</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-xl font-semibold text-slate-900">EUROCHAM Vote</h1>
            <p class="text-sm text-slate-500">Administration · Assemblée Générale 2026</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 border border-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Mot de passe</label>
                    <input id="password" name="password" type="password" required
                           class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                    Se souvenir de moi
                </label>
                <button type="submit"
                        class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Se connecter
                </button>
            </form>
        </div>
    </div>
</body>
</html>
