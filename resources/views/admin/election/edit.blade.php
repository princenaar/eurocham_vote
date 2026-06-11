@extends('layouts.admin')

@section('title', 'Scrutin & QR code')

@section('content')
    <div class="mb-6 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
        AG : <span class="font-semibold text-brand-800">{{ $assembly->name }}</span>.
        Vote : <span class="font-semibold text-brand-800">{{ $election->name }}</span>
        ({{ $election->typeLabel() }}).
        <br>
        État :
        <span class="font-semibold text-brand-800">{{ $election->statusLabel() }}</span>.
        @unless ($election->canEditConfiguration())
            La configuration est en lecture seule.
        @endunless
    </div>

    @if ($elections->count() > 1)
        <div class="mb-6 rounded-lg border border-slate-200 bg-white px-4 py-3">
            <label class="block text-sm font-medium text-slate-700">Changer de vote</label>
            <select onchange="window.location = this.value"
                    class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                @foreach ($elections as $option)
                    <option value="{{ route('admin.election.edit', ['election' => $option->id]) }}" @selected($option->id === $election->id)>
                        {{ $option->name }} — {{ $option->typeLabel() }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($election->isRunoff())
        <div class="mb-6 rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
            <strong>Vote exceptionnel de départage — tour {{ $election->current_round }}.</strong>
            Seuls les {{ count($election->runoff_candidate_ids) }} candidats à égalité sont au bulletin,
            pour {{ $election->runoff_seats }} siège(s).
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Live controls --}}
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <h2 class="font-serif text-base font-semibold text-brand-800">Contrôle en direct</h2>
            <p class="mt-1 text-xs text-slate-500">
                Le vote n’est accepté que si la fenêtre est ouverte ET le QR code actif.
            </p>

            <div class="mt-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-slate-800">Fenêtre de vote</div>
                        <div class="text-sm {{ $election->window_open ? 'text-emerald-600' : 'text-slate-500' }}">
                            {{ $election->window_open ? 'Ouverte' : 'Fermée' }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.election.window') }}">
                        @csrf
                        <input type="hidden" name="election_id" value="{{ $election->id }}">
                        <button type="submit"
                                @disabled(! $election->window_open && ! $election->canOpen())
                                class="rounded-md px-3 py-2 text-sm font-medium text-white {{ $election->window_open ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                            {{ $election->window_open ? 'Clôturer le vote' : 'Ouvrir le vote' }}
                        </button>
                    </form>
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <div>
                        <div class="text-sm font-medium text-slate-800">QR code</div>
                        <div class="text-sm {{ $election->qr_active ? 'text-emerald-600' : 'text-slate-500' }}">
                            {{ $election->qr_active ? 'Actif' : 'Inactif' }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.election.qr.toggle') }}">
                        @csrf
                        <input type="hidden" name="election_id" value="{{ $election->id }}">
                        <button type="submit"
                                class="rounded-md px-3 py-2 text-sm font-medium {{ $election->qr_active ? 'bg-slate-200 text-slate-800 hover:bg-slate-300' : 'bg-brand-800 text-white hover:bg-brand-700' }}">
                            {{ $election->qr_active ? 'Désactiver le QR' : 'Activer le QR' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- QR display --}}
        <div class="bg-white rounded-lg border border-slate-200 p-5 text-center">
            <h2 class="font-serif text-base font-semibold text-brand-800">QR code de vote</h2>
            <div class="mt-3 flex justify-center">
                <img src="{{ route('admin.election.qr') }}" alt="QR code de vote" class="w-56 h-56">
            </div>
            <p class="mt-2 text-xs text-slate-500 break-all">{{ $voteUrl }}</p>
            <a href="{{ route('admin.election.qr.fullscreen') }}"
               class="mt-3 inline-flex rounded-md border border-brand-200 bg-white px-3 py-2 text-sm font-medium text-brand-800 hover:bg-brand-50">
                Afficher le QR code en pleine page
            </a>
        </div>

        {{-- Settings --}}
        <div class="bg-white rounded-lg border border-slate-200 p-5 lg:col-span-2">
            <h2 class="font-serif text-base font-semibold text-brand-800">Paramètres du scrutin</h2>
            <form method="POST" action="{{ route('admin.election.update') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                <input type="hidden" name="election_id" value="{{ $election->id }}">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nom du scrutin</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $election->name) }}" required
                           @readonly(! $election->canEditConfiguration())
                           class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-brand-600 focus:ring-brand-600">
                </div>
                @if ($election->isBoardVote())
                    <div>
                        <label for="candidate_threshold" class="block text-sm font-medium text-slate-700 mb-1">
                            Nombre de sièges (seuil Mode A/B)
                        </label>
                        <input id="candidate_threshold" name="candidate_threshold" type="number" min="1" max="200"
                               value="{{ old('candidate_threshold', $election->candidate_threshold) }}" required
                               @readonly(! $election->canEditConfiguration())
                               class="w-32 rounded-md border-slate-300 shadow-sm text-sm focus:border-brand-600 focus:ring-brand-600">
                    </div>
                @endif
                <div class="sm:col-span-2 text-xs text-slate-500">
                    @if ($election->isBoardVote())
                        {{ $candidateCount }} candidat(s) enregistré(s) →
                        @if ($election->mode === \App\Models\Election::MODE_SELECT)
                            Mode A (sélection de {{ $election->candidate_threshold }}).
                        @elseif ($election->mode === \App\Models\Election::MODE_AUTO)
                            Mode B (élection automatique).
                        @else
                            mode non déterminé.
                        @endif
                    @else
                        {{ $questionCount }} question(s) enregistrée(s). Les électeurs répondent Oui, Non ou Abstention.
                    @endif
                </div>
                <div class="sm:col-span-2">
                    <button type="submit"
                            @disabled(! $election->canEditConfiguration())
                            class="rounded-md bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                        Enregistrer les paramètres
                    </button>
                </div>
            </form>
        </div>

        @if ($election->isQuestionsVote())
            <div class="bg-white rounded-lg border border-slate-200 p-5 lg:col-span-2">
                <h2 class="font-serif text-base font-semibold text-brand-800">Questions</h2>
                <div class="mt-4 overflow-hidden rounded-md border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-2 font-medium">Ordre</th>
                                <th class="px-4 py-2 font-medium">Titre</th>
                                <th class="px-4 py-2 font-medium">Détails</th>
                                <th class="px-4 py-2 font-medium text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($election->questions as $question)
                                <tr>
                                    <td class="px-4 py-2 text-slate-500">{{ $question->display_order }}</td>
                                    <td class="px-4 py-2 font-medium text-slate-900">{{ $question->title }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ $question->description }}</td>
                                    <td class="px-4 py-2 text-right">
                                        @if ($election->canEditConfiguration())
                                            <form method="POST" action="{{ route('admin.elections.questions.destroy', [$election, $question]) }}" class="inline"
                                                  onsubmit="return confirm('Supprimer cette question ?');">
                                                @csrf @method('DELETE')
                                                <button class="text-red-700 underline">Supprimer</button>
                                            </form>
                                        @else
                                            <span class="text-slate-400">Verrouillé</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-5 text-center text-slate-400">Aucune question.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($election->canEditConfiguration())
                    <form method="POST" action="{{ route('admin.elections.questions.store', $election) }}" class="mt-5 grid gap-4 sm:grid-cols-6">
                        @csrf
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Titre</label>
                            <input name="title" required class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-slate-700">Texte / détails</label>
                            <input name="description" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Ordre</label>
                            <input name="display_order" type="number" min="0" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600">
                        </div>
                        <div class="sm:col-span-6">
                            <button class="rounded-md bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                                Ajouter la question
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        @else
            <div class="bg-white rounded-lg border border-slate-200 p-5 lg:col-span-2">
                <h2 class="font-serif text-base font-semibold text-brand-800">Candidats</h2>
                <p class="mt-1 text-sm text-slate-600">La liste des candidats de ce vote CA se gère dans l’écran dédié.</p>
                <a href="{{ route('admin.candidates.index', ['election' => $election->id]) }}"
                   class="mt-3 inline-flex rounded-md border border-brand-200 bg-white px-3 py-2 text-sm font-medium text-brand-800 hover:bg-brand-50">
                    Gérer les candidats
                </a>
            </div>
        @endif
    </div>
@endsection
