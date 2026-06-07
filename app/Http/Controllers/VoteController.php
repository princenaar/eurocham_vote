<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Election;
use App\Models\Vote;
use App\Models\VoteSelection;
use App\Support\AuditLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Public, QR-gated voter flow (CLAUDE.md rules 1–6). Every electoral rule —
 * eligibility, window-open, exactly-N, one-vote-per-company — is enforced
 * server-side here; Alpine on the ballot is UX only (live counter, disabled submit).
 */
class VoteController extends Controller
{
    private const SESSION_COMPANY = 'vote.company_id';
    private const SESSION_REP = 'vote.representative';
    private const SESSION_PROXY = 'vote.proxy_company_name';

    /**
     * QR landing. Routes the voter to the right screen based on scrutin state:
     * closed window → closed notice; Mode B → auto-election result; Mode A → identity form.
     */
    public function start(Request $request): View
    {
        $election = Election::current();

        if (! $election->isVotingOpen() || $election->mode === null) {
            return view('vote.closed', ['election' => $election]);
        }

        if ($election->mode === Election::MODE_AUTO) {
            return view('vote.auto', [
                'election' => $election,
                'candidates' => $this->orderedCandidates(),
            ]);
        }

        return view('vote.start', [
            'election' => $election,
            'companies' => Company::query()->orderBy('name')->get()
                ->filter->isEligible()
                ->values(),
        ]);
    }

    /**
     * Eligibility gate (rules 1, 2, 5, 6). Validates the chosen company is on the
     * imported list, eligible, the window is open, and it has not already voted.
     * On success the voter identity is held in the session for the ballot step.
     */
    public function identify(Request $request): RedirectResponse
    {
        $election = Election::current();

        if (! $election->isVotingOpen() || $election->mode === null) {
            throw ValidationException::withMessages([
                'company_id' => 'Le scrutin n’est pas ouvert. Veuillez réessayer pendant la fenêtre de vote.',
            ]);
        }

        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'proxy_company_name' => ['nullable', 'string', 'max:255'],
        ], [
            'company_id.required' => 'Veuillez sélectionner votre entreprise membre.',
            'company_id.exists' => 'Entreprise inconnue. Veuillez contacter le secrétariat EUROCHAM.',
            'last_name.required' => 'Le nom est obligatoire.',
            'first_name.required' => 'Le prénom est obligatoire.',
        ]);

        $company = Company::find($data['company_id']);

        if (! $company->isEligible()) {
            throw ValidationException::withMessages([
                'company_id' => "L’entreprise « {$company->name} » n’est pas à jour de ses obligations "
                    .'et ne peut pas voter. Veuillez contacter le secrétariat EUROCHAM.',
            ]);
        }

        if ($company->hasVoted()) {
            throw ValidationException::withMessages([
                'company_id' => "L’entreprise « {$company->name} » a déjà voté. "
                    .'Le vote est unique et définitif.',
            ]);
        }

        $request->session()->put(self::SESSION_COMPANY, $company->id);
        $request->session()->put(self::SESSION_REP, [
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
        ]);
        $request->session()->put(self::SESSION_PROXY, $data['proxy_company_name'] ?? null);

        return redirect()->route('vote.ballot');
    }

    /**
     * Mode A ballot. Requires an identified, eligible, not-yet-voted company in session.
     */
    public function ballot(Request $request): View|RedirectResponse
    {
        $guard = $this->guardActiveVoter($request);
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        [$election, $company] = $guard;

        return view('vote.ballot', [
            'election' => $election,
            'company' => $company,
            'representative' => $request->session()->get(self::SESSION_REP),
            'proxyCompanyName' => $request->session()->get(self::SESSION_PROXY),
            'candidates' => $election->ballotCandidates(),
            'required' => $election->requiredSelections(),
            'isRunoff' => $election->isRunoff(),
        ]);
    }

    /**
     * Review screen before the final, irrevocable submit (rule 5). Re-validates the
     * exactly-N selection server-side so the confirmation always reflects a valid ballot.
     */
    public function review(Request $request): View|RedirectResponse
    {
        $guard = $this->guardActiveVoter($request);
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        [$election, $company] = $guard;
        $chosen = $this->validateSelections($request, $election);

        return view('vote.review', [
            'election' => $election,
            'company' => $company,
            'representative' => $request->session()->get(self::SESSION_REP),
            'proxyCompanyName' => $request->session()->get(self::SESSION_PROXY),
            'candidates' => Candidate::query()->whereIn('id', $chosen)
                ->orderBy('display_order')->orderBy('name')->get(),
            'chosen' => $chosen,
        ]);
    }

    /**
     * Final submit (rules 5 & 6). Re-checks window + eligibility + not-voted, then writes
     * the vote and its selections atomically with a unique timestamped reference number.
     * The DB UNIQUE(company_id) is the last line of defence against a double vote.
     */
    public function submit(Request $request): RedirectResponse
    {
        $guard = $this->guardActiveVoter($request);
        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        [$election, $company] = $guard;
        $chosen = $this->validateSelections($request, $election);

        $proxy = $request->session()->get(self::SESSION_PROXY);
        $representative = $request->session()->get(self::SESSION_REP);
        $reference = $this->generateReference();
        $round = $election->current_round;

        try {
            DB::transaction(function () use ($company, $round, $proxy, $reference, $chosen) {
                $vote = Vote::create([
                    'company_id' => $company->id,
                    'round' => $round,
                    'proxy_company_name' => $proxy,
                    'reference_number' => $reference,
                    'voted_at' => now(),
                ]);

                $rows = array_map(fn (int $candidateId) => [
                    'vote_id' => $vote->id,
                    'candidate_id' => $candidateId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $chosen);

                VoteSelection::insert($rows);
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent submission won the race on UNIQUE(company_id).
            $this->clearVoterSession($request);

            throw ValidationException::withMessages([
                'company_id' => "L’entreprise « {$company->name} » a déjà voté. "
                    .'Le vote est unique et définitif.',
            ]);
        }

        // Traceability without coupling personal identity to the ballot row (rule 7).
        AuditLogger::log(
            'vote.cast',
            "Vote enregistré pour « {$company->name} » (réf. {$reference})",
            [
                'company_id' => $company->id,
                'reference_number' => $reference,
                'representative' => $representative,
                'proxy_company_name' => $proxy,
            ],
        );

        $this->clearVoterSession($request);
        $request->session()->put('vote.reference', $reference);

        return redirect()->route('vote.confirmation');
    }

    /**
     * Confirmation screen showing the unique reference number (rule 5).
     */
    public function confirmation(Request $request): View|RedirectResponse
    {
        $reference = $request->session()->get('vote.reference');

        if (! $reference) {
            return redirect()->route('vote.start');
        }

        return view('vote.confirmation', ['reference' => $reference]);
    }

    /**
     * Ensure there is an identified, eligible, not-yet-voted company in session and the
     * scrutin is still an open Mode A. Returns [election, company] or a redirect/abort.
     *
     * @return array{0: Election, 1: Company}|RedirectResponse
     */
    private function guardActiveVoter(Request $request): array|RedirectResponse
    {
        $election = Election::current();

        if (! $election->isVotingOpen() || $election->mode !== Election::MODE_SELECT) {
            $this->clearVoterSession($request);

            return redirect()->route('vote.start');
        }

        $companyId = $request->session()->get(self::SESSION_COMPANY);
        $company = $companyId ? Company::find($companyId) : null;

        if (! $company || ! $company->isEligible() || $company->hasVoted()) {
            $this->clearVoterSession($request);

            return redirect()->route('vote.start');
        }

        return [$election, $company];
    }

    /**
     * Validate that exactly `requiredSelections()` distinct, real candidates were chosen
     * (rule 4). Throws a French validation error otherwise. Returns the chosen IDs.
     *
     * @return array<int, int>
     */
    private function validateSelections(Request $request, Election $election): array
    {
        $required = $election->requiredSelections();
        // In a runoff only the tied candidates are on the ballot — reject anything else.
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
     * @return \Illuminate\Database\Eloquent\Collection<int, Candidate>
     */
    private function orderedCandidates()
    {
        return Candidate::query()->orderBy('display_order')->orderBy('name')->get();
    }

    /**
     * Unique, human-readable, timestamped reference (rule 5). The DB UNIQUE column is the
     * guarantee; the retry loop keeps the displayed number collision-free.
     */
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
            self::SESSION_COMPANY,
            self::SESSION_REP,
            self::SESSION_PROXY,
        ]);
    }
}
