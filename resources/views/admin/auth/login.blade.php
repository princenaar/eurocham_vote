<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — EUROCHAM Vote</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-brand-950 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo-eurocham.png') }}" alt="EUROCHAM Sénégal"
                 class="mx-auto h-16 w-auto bg-white rounded-lg px-3 py-2 shadow-lg">
            <h1 class="mt-5 font-serif text-2xl font-semibold text-white">Administration</h1>
            <p class="text-sm text-gold-300">Vote électronique · Assemblée Générale 2026</p>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-xl border border-slate-200">
            <x-tricolor-bar />
            <div class="p-6">
                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 border border-red-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4" data-testid="admin-login-form">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Adresse e-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus data-testid="admin-email"
                               class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-brand-600 focus:ring-brand-600">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Mot de passe</label>
                        <input id="password" name="password" type="password" required data-testid="admin-password"
                               class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-brand-600 focus:ring-brand-600">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                        Se souvenir de moi
                    </label>
                    <button type="submit" data-testid="admin-login-submit"
                            class="w-full rounded-md bg-brand-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                        Se connecter
                    </button>
                </form>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            Chambre des Investisseurs Européens au Sénégal · Version 1
        </p>
    </div>
</body>
</html>
