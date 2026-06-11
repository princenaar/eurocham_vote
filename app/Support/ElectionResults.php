<?php

namespace App\Support;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\ElectionQuestion;
use App\Models\Vote;
use App\Models\VoteSelection;
use Illuminate\Support\Collection;

/**
 * Consolidates an election's results (CLAUDE.md rule 8) and detects boundary ties at
 * the last seat (Mode A), which the admin may resolve with a runoff round. The single
 * source of truth for "who is elected" — used by the public proclamation, the admin
 * results screen, and the Excel/PDF exports so they can never disagree.
 */
class ElectionResults
{
    public function __construct(private readonly Election $election) {}

    public static function for(Election $election): self
    {
        return new self($election);
    }

    /**
     * Candidates of a round ranked by that round's votes (desc), then name (asc).
     * Each row: ['candidate' => Candidate, 'votes' => int, 'rank' => int].
     *
     * @return Collection<int, array{candidate: Candidate, votes: int, rank: int}>
     */
    public function ranking(int $round): Collection
    {
        $counts = VoteSelection::query()
            ->join('votes', 'votes.id', '=', 'vote_selections.vote_id')
            ->where('votes.election_id', $this->election->id)
            ->where('votes.round', $round)
            ->groupBy('vote_selections.candidate_id')
            ->selectRaw('vote_selections.candidate_id as cid, COUNT(*) as total')
            ->pluck('total', 'cid');

        $candidates = $this->candidatesForRound($round);

        return $candidates
            ->map(fn (Candidate $c) => [
                'candidate' => $c,
                'votes' => (int) ($counts[$c->id] ?? 0),
            ])
            ->sort(fn ($a, $b) => $b['votes'] <=> $a['votes']
                ?: strcmp($a['candidate']->name, $b['candidate']->name))
            ->values()
            ->map(function ($row, $i) {
                $row['rank'] = $i + 1;

                return $row;
            });
    }

    /** Convenience: the main vote ranking (round 1). */
    public function mainRanking(): Collection
    {
        return $this->ranking(1);
    }

    public function votesCast(int $round = 1): int
    {
        return Vote::query()->forElection($this->election)->round($round)->count();
    }

    /**
     * The final elected Board. Mode B → all candidates. Mode A → round-1 clear winners
     * plus, for any contested seats, the winners of the latest runoff (if it resolved
     * the tie). Candidates still tied are left off until a runoff settles them.
     *
     * @return Collection<int, Candidate>
     */
    public function electedBoard(): Collection
    {
        if ($this->election->mode === Election::MODE_AUTO) {
            return $this->election->candidates()->get();
        }

        if ($this->election->mode !== Election::MODE_SELECT) {
            return collect();
        }

        [$winners, $tie] = $this->resolveContest($this->mainRanking(), $this->election->candidate_threshold);
        $board = $winners;

        if ($tie !== null && $this->election->isRunoff()) {
            [$runoffWinners] = $this->resolveContest(
                $this->ranking($this->election->current_round),
                (int) $this->election->runoff_seats,
            );
            $board = $board->merge($runoffWinners);
        }

        return $board->values();
    }

    /**
     * Whether a contested seat is still undecided (a boundary tie with no — or an
     * inconclusive — runoff). When true, the Board is not yet final.
     */
    public function hasUnresolvedTie(): bool
    {
        return $this->pendingTie() !== null;
    }

    /**
     * The tie that currently needs resolving, ready to seed a runoff:
     * ['tied' => Collection<Candidate>, 'seats' => int, 'votes' => int]. Null when the
     * Board is fully determined. During a runoff this reflects any deeper tie.
     *
     * @return array{tied: Collection<int, Candidate>, seats: int, votes: int}|null
     */
    public function pendingTie(): ?array
    {
        if ($this->election->mode !== Election::MODE_SELECT) {
            return null;
        }

        [, $tie] = $this->resolveContest($this->mainRanking(), $this->election->candidate_threshold);

        if ($tie === null) {
            return null;
        }

        if (! $this->election->isRunoff()) {
            return $tie;
        }

        // A runoff is underway — report its own boundary tie, if any (else resolved).
        [, $runoffTie] = $this->resolveContest(
            $this->ranking($this->election->current_round),
            (int) $this->election->runoff_seats,
        );

        return $runoffTie;
    }

    /**
     * Split a ranked contest into winners and (optionally) an ambiguous boundary tie.
     * Candidates strictly above the Nth-seat vote count win outright; if more candidates
     * tie on that count than there are remaining seats, those are returned as the tie.
     *
     * @param  Collection<int, array{candidate: Candidate, votes: int, rank: int}>  $ranking
     * @return array{0: Collection<int, Candidate>, 1: array{tied: Collection<int, Candidate>, seats: int, votes: int}|null}
     */
    private function resolveContest(Collection $ranking, int $seats): array
    {
        if ($ranking->count() <= $seats) {
            // Not a real contest (≤ seats candidates) — everyone wins, no tie.
            return [$ranking->pluck('candidate')->values(), null];
        }

        $boundaryVotes = $ranking[$seats - 1]['votes'];

        $clear = $ranking->filter(fn ($r) => $r['votes'] > $boundaryVotes)->pluck('candidate')->values();
        $tied = $ranking->filter(fn ($r) => $r['votes'] === $boundaryVotes)->pluck('candidate')->values();
        $remaining = $seats - $clear->count();

        if ($tied->count() === $remaining) {
            // The tied candidates fill exactly the remaining seats — no ambiguity.
            return [$clear->merge($tied)->values(), null];
        }

        return [$clear, ['tied' => $tied, 'seats' => $remaining, 'votes' => $boundaryVotes]];
    }

    /**
     * @return Collection<int, Candidate>
     */
    private function candidatesForRound(int $round): Collection
    {
        $query = $this->election->candidates();

        if ($round > 1 && ! empty($this->election->runoff_candidate_ids)) {
            $query->whereIn('id', $this->election->runoff_candidate_ids);
        }

        return $query->get();
    }

    /**
     * Results for a Oui/Non/Abstention vote. Percentages are calculated on expressed
     * Oui + Non only; abstention is reported separately and never wins.
     *
     * @return Collection<int, array{
     *     question: ElectionQuestion,
     *     yes: int,
     *     no: int,
     *     abstain: int,
     *     total: int,
     *     expressed: int,
     *     yes_percent: float,
     *     no_percent: float,
     *     result: string
     * }>
     */
    public function questionResults(): Collection
    {
        $questions = $this->election->questions()->get();

        $counts = \App\Models\QuestionResponse::query()
            ->join('votes', 'votes.id', '=', 'question_responses.vote_id')
            ->where('votes.election_id', $this->election->id)
            ->selectRaw('question_responses.election_question_id as qid')
            ->selectRaw('SUM(CASE WHEN question_responses.answer = 1 THEN 1 ELSE 0 END) as yes_count')
            ->selectRaw('SUM(CASE WHEN question_responses.answer = 0 THEN 1 ELSE 0 END) as no_count')
            ->selectRaw('SUM(CASE WHEN question_responses.answer IS NULL THEN 1 ELSE 0 END) as abstain_count')
            ->selectRaw('COUNT(*) as total_count')
            ->groupBy('question_responses.election_question_id')
            ->get()
            ->keyBy('qid');

        return $questions->map(function (ElectionQuestion $question) use ($counts) {
            $row = $counts->get($question->id);
            $yes = (int) ($row->yes_count ?? 0);
            $no = (int) ($row->no_count ?? 0);
            $abstain = (int) ($row->abstain_count ?? 0);
            $total = (int) ($row->total_count ?? 0);
            $expressed = $yes + $no;

            return [
                'question' => $question,
                'yes' => $yes,
                'no' => $no,
                'abstain' => $abstain,
                'total' => $total,
                'expressed' => $expressed,
                'yes_percent' => $expressed > 0 ? round($yes / $expressed * 100, 1) : 0.0,
                'no_percent' => $expressed > 0 ? round($no / $expressed * 100, 1) : 0.0,
                'result' => $this->questionWinner($yes, $no, $expressed),
            ];
        });
    }

    private function questionWinner(int $yes, int $no, int $expressed): string
    {
        if ($expressed === 0) {
            return 'Aucun suffrage exprimé';
        }

        if ($yes === $no) {
            return 'Égalité';
        }

        return $yes > $no ? 'Oui' : 'Non';
    }
}
