<?php

namespace App\Http\Controllers;

use App\Models\AssemblyCompany;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\QuestionResponse;
use App\Models\Vote;
use App\Models\VoteSelection;
use App\Support\AuditLogger;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Public QR-gated voter flow. It resolves the one globally active vote and then
 * keeps the original CA flow or renders the Oui/Non/Abstention question flow.
 */
class VoteController extends Controller
{
    private const SESSION_ELECTION = 'vote.election_id';
    private const SESSION_COMPANY = 'vote.company_id';
    private const SESSION_ASSEMBLY_COMPANY = 'vote.assembly_company_id';
    private const SESSION_REP = 'vote.representative';
    private const SESSION_PROXY = 'vote.is_proxy';

    public function start(Request $request): View
    {
        $election = Election::active();

        if (! $election || ! $election->isVotingOpen() || ($election->isBoardVote() && $election->mode === null)) {
            return view('vote.closed', ['election' => $election ?? Election::current()]);
        }

        if ($election->isBoardVote() && $election->mode === Election::MODE_AUTO) {
            return view('vote.auto', [
                'election' => $election,
                'candidates' => $election->candidates()->get(),
            ]);
        }

        return view('vote.start', [
            'election' => $election,
            'companies' => $election->assembly->eligibleCompanies()->orderBy('name')->get(),
        ]);
    }

    public function identify(Request $request): RedirectResponse
    {
        $election = Election::active();

        if (! $election || ! $election->isVotingOpen() || ($election->isBoardVote() && $election->mode === null)) {
            throw ValidationException::withMessages([
                'company_id' => 'Le scrutin n’est pas ouvert. Veuillez réessayer pendant la fenêtre de vote.',
            ]);
        }

        $request->validate([
            'assembly_company_id' => ['nullable', 'integer', 'exists:assembly_companies,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'is_proxy' => ['nullable', 'boolean'],
        ], [
            'assembly_company_id.exists' => 'Entreprise inconnue. Veuillez contacter le secrétariat EUROCHAM.',
            'company_id.exists' => 'Entreprise inconnue. Veuillez contacter le secrétariat EUROCHAM.',
            'last_name.required' => 'Le nom est obligatoire.',
            'first_name.required' => 'Le prénom est obligatoire.',
        ]);

        $assemblyCompany = $this->resolveAssemblyCompany($request, $election);

        if (! $assemblyCompany) {
            throw ValidationException::withMessages([
                'company_id' => 'Veuillez sélectionner votre entreprise membre.',
            ]);
        }

        if (! $assemblyCompany->eligible) {
            throw ValidationException::withMessages([
                'company_id' => "L’entreprise « {$assemblyCompany->name} » n’est pas à jour de ses obligations "
                    .'de cotisation et d’enquête, et ne peut pas voter. Veuillez contacter le secrétariat EUROCHAM.',
            ]);
        }

        if ($assemblyCompany->hasVoted($election)) {
            throw ValidationException::withMessages([
                'company_id' => "L’entreprise « {$assemblyCompany->name} » a déjà voté. "
                    .'Le vote est unique et définitif.',
            ]);
        }

        $request->session()->put(self::SESSION_ELECTION, $election->id);
        $request->session()->put(self::SESSION_ASSEMBLY_COMPANY, $assemblyCompany->id);
        $request->session()->put(self::SESSION_COMPANY, $assemblyCompany->company_id);
        $request->session()->put(self::SESSION_REP, [
            'last_name' => $request->input('last_name'),
            'first_name' => $request->input('first_name'),
        ]);
        $request->session()->put(self::SESSION_PROXY, (bool) $request->input('is_proxy', false));

        return redirect()->route('vote.ballot');
    }

    public function ballot(Request $request): View|RedirectResponse
    {
        $guard = $this->guardActiveVoter($request);
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        [$election, $assemblyCompany] = $guard;

        if ($election->isQuestionsVote()) {
            return view('vote.questions-ballot', [
                'election' => $election,
                'company' => $assemblyCompany,
                'representative' => $request->session()->get(self::SESSION_REP),
                'isProxy' => (bool) $request->session()->get(self::SESSION_PROXY, false),
                'questions' => $election->questions()->get(),
            ]);
        }

        return view('vote.ballot', [
            'election' => $election,
            'company' => $assemblyCompany,
            'representative' => $request->session()->get(self::SESSION_REP),
            'isProxy' => (bool) $request->session()->get(self::SESSION_PROXY, false),
            'candidates' => $election->ballotCandidates(),
            'required' => $election->requiredSelections(),
            'isRunoff' => $election->isRunoff(),
        ]);
    }

    public function review(Request $request): View|RedirectResponse
    {
        $guard = $this->guardActiveVoter($request);
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        [$election, $assemblyCompany] = $guard;

        if ($election->isQuestionsVote()) {
            $answers = $this->validateQuestionAnswers($request, $election);

            return view('vote.questions-review', [
                'election' => $election,
                'company' => $assemblyCompany,
                'representative' => $request->session()->get(self::SESSION_REP),
                'isProxy' => (bool) $request->session()->get(self::SESSION_PROXY, false),
                'questions' => $election->questions()->get(),
                'answers' => $answers,
                'labels' => [
                    'yes' => 'Oui',
                    'no' => 'Non',
                    'abstain' => 'Abstention',
                ],
            ]);
        }

        $chosen = $this->validateSelections($request, $election);

        return view('vote.review', [
            'election' => $election,
            'company' => $assemblyCompany,
            'representative' => $request->session()->get(self::SESSION_REP),
            'isProxy' => (bool) $request->session()->get(self::SESSION_PROXY, false),
            'candidates' => $election->candidates()->whereIn('id', $chosen)->get(),
            'chosen' => $chosen,
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $guard = $this->guardActiveVoter($request);
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        [$election, $assemblyCompany] = $guard;
        $round = $election->current_round;
        $isProxy = (bool) $request->session()->get(self::SESSION_PROXY, false);
        $representative = $request->session()->get(self::SESSION_REP);

        $chosen = $election->isBoardVote() ? $this->validateSelections($request, $election) : [];
        $answers = $election->isQuestionsVote() ? $this->validateQuestionAnswers($request, $election) : [];

        $lock = Cache::store(config('cache.vote_lock_store'))
            ->lock("vote:company:{$assemblyCompany->company_id}:round:{$round}", 10);

        try {
            $reference = $lock->block(3, function () use ($election, $assemblyCompany, $round, $isProxy, $chosen, $answers) {
                $this->assertNotYetVoted($assemblyCompany, $election, $round);

                $reference = $this->generateReference();

                DB::transaction(function () use ($election, $assemblyCompany, $round, $isProxy, $reference, $chosen, $answers) {
                    $vote = Vote::create([
                        'election_id' => $election->id,
                        'company_id' => $assemblyCompany->company_id,
                        'assembly_company_id' => $assemblyCompany->id,
                        'round' => $round,
                        'is_proxy' => $isProxy,
                        'reference_number' => $reference,
                        'voted_at' => now(),
                    ]);

                    if ($election->isBoardVote()) {
                        $rows = array_map(fn (int $candidateId) => [
                            'vote_id' => $vote->id,
                            'candidate_id' => $candidateId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ], $chosen);

                        VoteSelection::insert($rows);
                    } else {
                        $rows = collect($answers)->map(fn (string $answer, int $questionId) => [
                            'vote_id' => $vote->id,
                            'election_question_id' => $questionId,
                            'answer' => $this->answerToDatabaseValue($answer),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])->values()->all();

                        QuestionResponse::insert($rows);
                    }
                });

                return $reference;
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                $election->isBoardVote() ? 'candidates' : 'answers' => 'Le système est momentanément occupé. '
                    .'Veuillez réessayer dans quelques instants.',
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->clearVoterSession($request);

            throw ValidationException::withMessages([
                'company_id' => "L’entreprise « {$assemblyCompany->name} » a déjà voté. "
                    .'Le vote est unique et définitif.',
            ]);
        }

        AuditLogger::log(
            'vote.cast',
            "Vote enregistré pour « {$assemblyCompany->name} » (réf. {$reference})",
            [
                'assembly_id' => $election->assembly_id,
                'election_id' => $election->id,
                'company_id' => $assemblyCompany->company_id,
                'assembly_company_id' => $assemblyCompany->id,
                'reference_number' => $reference,
                'representative' => $representative,
                'is_proxy' => $isProxy,
            ],
        );

        $this->clearVoterSession($request);
        $request->session()->put('vote.reference', $reference);

        return redirect()->route('vote.confirmation');
    }

    public function confirmation(Request $request): View|RedirectResponse
    {
        $reference = $request->session()->get('vote.reference');

        if (! $reference) {
            return redirect()->route('vote.start');
        }

        return view('vote.confirmation', ['reference' => $reference]);
    }

    /**
     * @return array{0: Election, 1: AssemblyCompany}|RedirectResponse
     */
    private function guardActiveVoter(Request $request): array|RedirectResponse
    {
        $election = Election::active();
        $sessionElectionId = $request->session()->get(self::SESSION_ELECTION);

        if (! $election || ! $election->isVotingOpen() || (int) $sessionElectionId !== $election->id) {
            $this->clearVoterSession($request);

            return redirect()->route('vote.start');
        }

        if ($election->isBoardVote() && $election->mode !== Election::MODE_SELECT) {
            $this->clearVoterSession($request);

            return redirect()->route('vote.start');
        }

        $assemblyCompanyId = $request->session()->get(self::SESSION_ASSEMBLY_COMPANY);
        $assemblyCompany = $assemblyCompanyId ? AssemblyCompany::find($assemblyCompanyId) : null;

        if (
            ! $assemblyCompany
            || $assemblyCompany->assembly_id !== $election->assembly_id
            || ! $assemblyCompany->eligible
            || $assemblyCompany->hasVoted($election)
        ) {
            $this->clearVoterSession($request);

            return redirect()->route('vote.start');
        }

        return [$election, $assemblyCompany];
    }

    private function assertNotYetVoted(AssemblyCompany $assemblyCompany, Election $election, int $round): void
    {
        if ($assemblyCompany->hasVoted($election, $round)) {
            throw ValidationException::withMessages([
                'company_id' => "L’entreprise « {$assemblyCompany->name} » a déjà voté. "
                    .'Le vote est unique et définitif.',
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    private function validateSelections(Request $request, Election $election): array
    {
        $required = $election->requiredSelections();
        $allowed = $election->ballotCandidates()->pluck('id')->all();

        $validated = $request->validate([
            'candidates' => ['required', 'array'],
            'candidates.*' => ['integer', 'distinct', Rule::in($allowed)],
        ], [
            'candidates.required' => "Vous devez sélectionner exactement {$required} candidat(s).",
            'candidates.*.in' => 'Candidat non éligible pour ce scrutin.',
        ]);

        $chosen = array_values(array_unique(array_map('intval', $validated['candidates'])));

        if (count($chosen) !== $required) {
            throw ValidationException::withMessages([
                'candidates' => "Vous devez sélectionner exactement {$required} candidat(s) "
                    .'(ni plus, ni moins).',
            ]);
        }

        return $chosen;
    }

    /**
     * @return array<int, string>
     */
    private function validateQuestionAnswers(Request $request, Election $election): array
    {
        $questionIds = $election->questions()->pluck('id')->all();
        $answers = $request->input('answers', []);

        if (! is_array($answers)) {
            throw ValidationException::withMessages(['answers' => 'Veuillez répondre à toutes les questions.']);
        }

        $normalized = [];
        foreach ($questionIds as $questionId) {
            $answer = $answers[$questionId] ?? null;

            if (! in_array($answer, ['yes', 'no', 'abstain'], true)) {
                throw ValidationException::withMessages([
                    "answers.{$questionId}" => 'Veuillez choisir Oui, Non ou Abstention.',
                ]);
            }

            $normalized[(int) $questionId] = $answer;
        }

        return $normalized;
    }

    private function answerToDatabaseValue(string $answer): ?bool
    {
        return match ($answer) {
            'yes' => true,
            'no' => false,
            default => null,
        };
    }

    private function resolveAssemblyCompany(Request $request, Election $election): ?AssemblyCompany
    {
        if ($request->filled('assembly_company_id')) {
            return AssemblyCompany::query()
                ->where('assembly_id', $election->assembly_id)
                ->find($request->integer('assembly_company_id'));
        }

        if ($request->filled('company_id')) {
            return AssemblyCompany::query()
                ->where('assembly_id', $election->assembly_id)
                ->where('company_id', $request->integer('company_id'))
                ->first();
        }

        return null;
    }

    private function generateReference(): string
    {
        do {
            $reference = 'EC2026-'.now()->format('ymd-His').'-'.Str::upper(Str::random(4));
        } while (Vote::query()->where('reference_number', $reference)->exists());

        return $reference;
    }

    private function clearVoterSession(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_ELECTION,
            self::SESSION_COMPANY,
            self::SESSION_ASSEMBLY_COMPANY,
            self::SESSION_REP,
            self::SESSION_PROXY,
        ]);
    }
}
