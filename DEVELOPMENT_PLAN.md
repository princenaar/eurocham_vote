# Development Plan — EUROCHAM Electronic Voting Platform

Derived from `Proposition_Technique_EUROCHAM_CheikhDiagne.docx` (ref. `P01.EUROCHAM.2026`).
Read [`CLAUDE.md`](./CLAUDE.md) first — the **Critical electoral rules** section governs every phase
below. T0 = 2 Jun 2026; AG = 18 Jun 2026.

## Milestones (from the proposition planning)

| Phase | Activity | Target date |
|-------|----------|-------------|
| 0 | Cadrage & Analyse (scoping) | 3 Jun (T0+1) |
| 1 | Conception & BDD (design + DB) | 4 Jun (T0+2) |
| 2 | Développement | 9 Jun (T0+5) |
| 3 | Tests & Recette (QA/acceptance) | 11 Jun (T0+7) |
| 4 | Déploiement (deploy) | 13 Jun (T0+9) |
| 5 | Formation EUROCHAM (training) | 15 Jun (T0+11) |
| — | AG day support + live results | 18 Jun |

Total estimate: ~10 person-days (excl. post-AG support).

---

## Phase 0 — Scaffolding & scoping

- [x] Laravel scaffolded (Laravel **13**, not 11 — latest stable; see CLAUDE.md).
- [x] Install deps: `maatwebsite/excel`, `barryvdh/laravel-dompdf`, `simplesoftwareio/simple-qrcode`,
      `predis/predis`.
- [x] Configure DB + drivers: **MySQL** (WAMP, InnoDB forced in `config/database.php`), French locale.
      Local dev uses the `database` session/cache driver (no Redis on this machine); production uses
      **Redis** per `.env.example` (sessions + cache + queue, HTTPS-only, secure cookies).
- [x] Tailwind v4 + Alpine.js via Vite; base French Blade layout (`resources/views/layouts/app.blade.php`).
- [ ] Confirm scrutin parameters with EUROCHAM at the cadrage meeting (candidate count → Mode A/B).
      *(External — pending EUROCHAM.)*

## Phase 1 — Data model & migrations

Design the schema; enforce constraints in DB where possible.

- [x] `companies` (eligible members): name, `normalized_name` (unique, for matching imports),
      eligibility flags (`survey_2025` / `dues_2025` / `new_member_2026`). Eligibility derived in
      `Company::isEligible()` per CLAUDE.md rule 2.
- [x] `candidates`: name, `display_order`, `auto_elected` flag (Mode B).
- [x] `elections` / settings: `mode` (A/B), `window_open`/`closed`, `candidate_threshold` (20),
      `qr_active` flag, `opened_at`/`closed_at`. Mode resolved by `Election::resolveMode()`.
- [x] `votes`: voting company (unique → enforces one-vote), optional `proxy_company_name`, `voted_at`,
      `reference_number` (unique). Per-candidate choices kept in `vote_selections`, not on `votes`.
- [x] `vote_selections`: chosen candidates per vote (Mode A); unique `(vote_id, candidate_id)`.
- [x] `audit_logs`: append-only activity journal (`created_at` only, no `updated_at`).
- [x] Unique constraint on `votes.company_id` makes double-vote impossible at the DB layer
      *(verified via tinker: second insert raises `UniqueConstraintViolationException`)*.

## Phase 2 — Back-office (admin)

- [x] Auth-protected admin area (login/password) — `Admin\AuthController`, `auth` middleware,
      guests redirected to `admin.login`. Seeded admin (`admin@eurocham.sn` / `eurocham2026`,
      overridable via `ADMIN_EMAIL`/`ADMIN_PASSWORD`).
- [x] Import eligible companies from Excel/CSV (`CompaniesImport`, Laravel Excel): flexible French
      headers, boolean parsing, upsert by normalized name, error report for invalid rows.
- [x] CRUD candidates; auto Mode A/B from count (`Election::syncModeFromCandidates()`, > threshold → A,
      ≤ threshold → B; Mode B flags all `auto_elected`).
- [x] Generate + display QR code (SVG, no image ext needed); remote **toggle** voting window + QR.
- [x] Live dashboard: company/candidate/vote counts + participation rate vs eligible.
- [x] Per-candidate results table (ranked by voix).
- [x] Export results to Excel (`ResultsExport`) + PDF (DomPDF, French/DejaVu) — verified valid output.
- All admin actions write to the append-only `audit_logs` via `Support\AuditLogger`.
- Tests: `ElectionModeTest`, `AdminAccessTest`, `CompaniesImportTest` (11 passing).

## Phase 3 — Voter flow (QR-gated, public)

Server-side enforcement of every rule; Alpine only for UX.

- [x] QR landing → eligibility form (name, first name, **searchable** member-company dropdown,
      optional proxy company). `VoteController@start` routes by scrutin state (closed/Mode A/Mode B).
- [x] Eligibility check against imported list (server re-validates by company id); explicit
      "contact secretariat" message on unknown/ineligible company.
- [x] Reject if voting window closed or company already voted (re-checked at every step + submit).
- [x] **Mode A** ballot: full candidate list, live Alpine counter, submit enabled only at exactly N;
      server re-enforces `count === requiredSelections()` at submit regardless of browser state.
- [x] **Mode B**: skip ballot, show auto-election result (`vote.auto`); `/vote/bulletin` redirects back.
- [x] Review screen → final submit → unique timestamped reference number `EC2026-ymd-His-XXXX`
      (final & irrevocable), written with selections in a DB transaction; `vote.cast` audit logged
      (representative name → audit only, per rule 7).
- [x] Block + warn on any second attempt: pre-check at identify + DB `UNIQUE(company_id)` caught at
      submit (`UniqueConstraintViolationException`) for the concurrency race. Redis atomic lock
      deferred to Phase 5.
- Tests: `VoterFlowTest` (11 passing) — window closed, eligibility, exactly-N (under/over/exact),
  double-vote, Mode B short-circuit, mid-session close, review + confirmation render.

## Phase 4 — Results & proclamation

- [x] Auto-consolidate results at window close; in-room display view. Public `/resultats`
      (`ResultsController`) reveals results **only after the window is closed** (`closed_at` set &
      `!window_open`); while open it shows turnout only — never choices (rule 7). `<meta refresh>`
      poll flips the projected display to results automatically at close.
- [x] Read-only public results mode after AG — `/resultats` is public, read-only, shows the elected
      Board + full ranking; admin sidebar links to it ("Affichage public").
- [x] Excel + PDF official report generation — built in Phase 2; **refactored** onto the shared
      `Support\ElectionResults` so ranking + "Élu" flag + runoff section match the screens exactly.
- [x] **Tiebreaker runoff (vote de départage):** `ElectionResults` detects a boundary tie at the last
      seat(s) (top-N + flag, rule 8). Admin launches `ElectionController@launchRunoff` (window must be
      closed) → re-opens a **restricted** scrutin (new `round`) among only the tied candidates for the
      remaining seats; voter flow enforces the restricted ballot + per-round one-vote
      (`votes UNIQUE(company_id, round)`). Final Board = round-1 clear winners + runoff winners.
- Data model additions: `votes.round`, `elections.{current_round, runoff_candidate_ids, runoff_seats}`
  (migration `2026_06_07_000001`, applied on MySQL).
- Tests: `ElectionResultsTest` (5) + `RunoffAndResultsTest` (9) — ranking, elected board, tie
  detection, runoff resolution, runoff launch guards, restricted ballot, round-aware re-vote, public
  gating (open/closed/Mode B), admin render + exports. Full suite **36 passing**.

## Phase 5 — Security, performance, QA

- [~] HTTPS/SSL-TLS enforced; CSRF on all forms; hash sensitive data. CSRF is on every form;
      passwords hashed (bcrypt); `.env.example` sets HTTPS URL, `SESSION_ENCRYPT`, secure cookies.
      *Pending:* app-level HTTPS force + security-headers middleware (deferred — not yet built).
- [x] Redis-backed sessions + **locking to guarantee anti-double-vote under concurrency**.
      `VoteController@submit` serialises writes per company+round with an atomic `Cache::lock`
      (Redis in prod; configured store locally) and re-checks not-yet-voted **inside** the lock, so
      simultaneous requests cannot both pass; brief block-and-retry (3 s) with a French
      "système occupé, réessayez" message on contention. DB `UNIQUE(company_id, round)` is the
      ultimate guarantee. Tested: `VoterFlowTest` "serialises submission per company".
- [ ] Load test for **150+ concurrent users** within the 30-min window. *(Not built — needs
      k6/Artillery + Redis infra; out of scope this session.)*
- [x] Feature tests for each critical rule: eligibility, exactly-N (Mode A), Mode B auto-election,
      window open/closed, one-vote-per-company, reference-number generation, runoff, results gating,
      concurrency lock — **37 tests passing**.
- [ ] Acceptance/test session with EUROCHAM (13 Jun); admin + voter user guides. *(External / not
      yet written.)*

## Phase 6 — Deployment & AG day

- [ ] Deploy to secured Cloud VPS (SLA 99.9%), HTTPS, backups.
- [ ] Generate final QR code (17 Jun) and hand over.
- [ ] On-site support 18 Jun; deliver Excel+PDF report within 24h, raw logs within 48h.

## Suggested test command targets

```bash
php artisan test --filter=EligibilityTest
php artisan test --filter=ModeAExactlyTwentyTest
php artisan test --filter=DoubleVoteTest
php artisan test --filter=VotingWindowTest
```
