@extends('layouts.app')

@section('title', 'EUROCHAM Sénégal — Assemblée Générale 2026')

@section('content')
    @php
        $ctaClasses = $cta['state'] === 'open'
            ? 'bg-gold-500 text-brand-950 hover:bg-gold-300 ring-gold-300'
            : 'bg-white/10 text-white hover:bg-white/20 ring-white/30';
    @endphp

    {{-- Hero --}}
    <section class="reveal relative overflow-hidden rounded-2xl bg-brand-950 text-white shadow-xl"
             style="animation-delay: .05s">
        {{-- Decorative tricolor glow + subtle grid --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-[0.18]"
             style="background:
                radial-gradient(60% 80% at 85% 0%, var(--color-brand-600), transparent 60%),
                radial-gradient(50% 70% at 10% 100%, var(--color-brand-700), transparent 55%);"></div>
        <div aria-hidden="true" class="absolute right-0 top-0 h-full w-1.5 flex flex-col">
            <span class="flex-1 bg-sng-green"></span>
            <span class="flex-1 bg-sng-gold"></span>
            <span class="flex-1 bg-sng-red"></span>
        </div>

        <div class="relative px-6 py-12 sm:px-12 sm:py-16 max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium uppercase tracking-wider text-gold-300">
                Chambre des Investisseurs Européens au Sénégal
            </div>
            <h1 class="mt-5 font-serif text-4xl sm:text-5xl font-semibold leading-tight tracking-tight">
                Assemblée Générale 2026
            </h1>
            <p class="mt-3 font-serif text-xl text-brand-100">
                Élection du Conseil d’Administration
            </p>
            <p class="mt-5 max-w-xl text-sm sm:text-base text-slate-300 leading-relaxed">
                Le vote électronique d’EUROCHAM Sénégal permet à chaque entreprise membre
                d’élire son nouveau Conseil d’Administration, en toute sécurité et en toute
                confidentialité, directement depuis son téléphone.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <a href="{{ $cta['route'] }}"
                   class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold shadow-sm ring-1 transition {{ $ctaClasses }}">
                    {{ $cta['label'] }}
                    <span aria-hidden="true">→</span>
                </a>
                <div class="flex items-center gap-2 text-sm text-slate-300">
                    <span class="inline-block h-2 w-2 rounded-full {{ $cta['state'] === 'open' ? 'bg-emerald-400 animate-pulse' : 'bg-gold-500' }}"></span>
                    @if ($cta['state'] === 'open')
                        Le scrutin est ouvert
                    @elseif ($cta['state'] === 'closed')
                        Résultats proclamés
                    @else
                        18 juin 2026 · fenêtre de vote de 30 minutes
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- À propos d'EUROCHAM --}}
    <section class="reveal mt-12 grid gap-8 lg:grid-cols-3" style="animation-delay: .15s">
        <div class="lg:col-span-1">
            <h2 class="font-serif text-2xl font-semibold text-brand-800">À propos d’EUROCHAM</h2>
            <div class="mt-3 h-1 w-16 flex">
                <span class="flex-1 bg-sng-green"></span>
                <span class="flex-1 bg-sng-gold"></span>
                <span class="flex-1 bg-sng-red"></span>
            </div>
        </div>
        <div class="lg:col-span-2 text-sm sm:text-base text-slate-600 leading-relaxed space-y-4">
            <p>
                La <strong class="text-brand-800">Chambre des Investisseurs Européens au Sénégal</strong>
                (EUROCHAM) fédère les entreprises et investisseurs européens présents au Sénégal.
                Elle œuvre au renforcement du dialogue économique, à la promotion des
                investissements et à la défense des intérêts de ses membres.
            </p>
            <p>
                Chaque année, l’Assemblée Générale réunit les entreprises membres pour décider
                des grandes orientations de la Chambre et renouveler ses instances de gouvernance.
            </p>
        </div>
    </section>

    {{-- L'enjeu de l'AG --}}
    <section class="reveal mt-12" style="animation-delay: .25s">
        <div class="rounded-2xl border border-brand-100 bg-white p-6 sm:p-8 shadow-sm">
            <div class="grid gap-6 sm:grid-cols-3">
                <div>
                    <div class="font-serif text-3xl font-semibold text-brand-800">18 juin 2026</div>
                    <p class="mt-1 text-sm text-slate-500">Date de l’Assemblée Générale</p>
                </div>
                <div>
                    <div class="font-serif text-3xl font-semibold text-brand-800">30 min</div>
                    <p class="mt-1 text-sm text-slate-500">Fenêtre de vote unique, en séance</p>
                </div>
                <div>
                    <div class="font-serif text-3xl font-semibold text-brand-800">1 voix</div>
                    <p class="mt-1 text-sm text-slate-500">Par entreprise membre éligible</p>
                </div>
            </div>
            <p class="mt-6 text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-6">
                L’Assemblée Générale 2026 procède à l’élection des membres du
                <strong class="text-brand-800">Conseil d’Administration</strong>. Le vote est
                ouvert à distance par le secrétariat pendant une fenêtre unique : passé ce délai,
                aucun nouveau bulletin n’est accepté. Chaque entreprise membre dispose d’une voix
                unique. Une procuration est enregistrée comme vote distinct pour l’entreprise représentée.
            </p>
        </div>
    </section>

    {{-- Comment voter --}}
    <section class="reveal mt-12" style="animation-delay: .35s">
        <h2 class="font-serif text-2xl font-semibold text-brand-800">Comment voter</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $steps = [
                    ['1', 'Scanner le QR code', 'Scannez le QR code affiché en salle avec votre téléphone — aucune application à installer.'],
                    ['2', 'Vérifier l’éligibilité', 'Sélectionnez votre entreprise. Le système confirme votre éligibilité à voter.'],
                    ['3', 'Choisir les candidats', 'Composez votre bulletin selon les règles du scrutin, avec un compteur en direct.'],
                    ['4', 'Confirmer le vote', 'Validez après vérification. Vous recevez une référence horodatée — le vote est définitif.'],
                ];
            @endphp
            @foreach ($steps as [$n, $title, $desc])
                <div class="relative rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-800 font-serif text-lg font-semibold text-gold-300">
                        {{ $n }}
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-brand-800">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Closing CTA --}}
    <section class="reveal mt-12 mb-4 rounded-2xl bg-brand-50 border border-brand-100 px-6 py-8 sm:px-10 text-center"
             style="animation-delay: .45s">
        <h2 class="font-serif text-2xl font-semibold text-brand-800">
            @if ($cta['state'] === 'open')
                Le scrutin est ouvert
            @elseif ($cta['state'] === 'closed')
                Consultez les résultats
            @else
                Le jour de l’Assemblée Générale
            @endif
        </h2>
        <p class="mt-2 text-sm text-slate-600 max-w-xl mx-auto">
            @if ($cta['state'] === 'open')
                Accédez au bulletin de vote dès maintenant pour exprimer la voix de votre entreprise.
            @elseif ($cta['state'] === 'closed')
                La composition du nouveau Conseil d’Administration est désormais disponible.
            @else
                Le QR code de vote sera activé en salle au moment de l’ouverture du scrutin.
            @endif
        </p>
        <a href="{{ $cta['route'] }}"
           class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-800 px-6 py-3 text-sm font-semibold text-white shadow-sm ring-1 ring-brand-700 transition hover:bg-brand-700">
            {{ $cta['label'] }}
            <span aria-hidden="true">→</span>
        </a>
    </section>
@endsection
