# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## Project

Electronic voting platform for **EUROCHAM Sénégal**'s Annual General Assembly process. The platform
now supports multiple Assemblées Générales (AG), each with multiple votes/scrutins, while preserving
the original 2026 Board of Directors (Conseil d'Administration) election flow. Voters access the
currently active vote by scanning a QR code in the room (no app install). The full technical
proposition (source of truth for original requirements) is
`Proposition_Technique_EUROCHAM_CheikhDiagne.docx`; the phased build is in
[`DEVELOPMENT_PLAN.md`](./DEVELOPMENT_PLAN.md).

Reference for the initial AG: `P01.EUROCHAM.2026`. The document and election are in **French** —
keep all voter- and admin-facing UI strings in French.

## Tech stack (fixed by the proposition — do not substitute)

| Layer | Choice |
|-------|--------|
| Back-end | PHP 8.4 / **Laravel 13** (MVC) |
| Database | MySQL 8.0 |
| Front-end | Blade + **TailwindCSS** + **Alpine.js** |
| QR code | PHP QR Code library (dynamically generated) |
| Excel export/import | **Laravel Excel** (Maatwebsite) |
| PDF export | **DomPDF** (`barryvdh/laravel-dompdf`) |
| Sessions / anti-double-vote | **Redis** |
| Security | HTTPS + SSL/TLS, CSRF tokens, hashing of sensitive data |
| Capacity target | 150+ concurrent users without degradation |

## Critical electoral rules (correctness-critical)

These rules define real institutional votes. Getting them wrong is a correctness failure, not a style
issue.

1. **AG owns votes:** all votes belong to an `Assembly`. Creating a new AG must preserve historical
   votes/results from previous AGs.
2. **Eligibility is snapshotted per AG:** imported companies are copied into `assembly_companies`.
   Historical results must use that AG snapshot, not a later global company list.
3. **One member company = one vote per vote/scrutin** (`voix unique`). Identity = member company,
   not the person. A company may vote once in each vote of an AG.
4. **One active vote globally:** only one vote may be open/QR-active across the whole system. Public
   `/vote` resolves the globally active vote.
5. **Eligibility rule:** a company is eligible if up to date on annual survey 2025 AND annual dues
   2025, OR (for new members) entry fees + 2026 dues. Unknown/ineligible company → explicit message
   to contact the secretariat.
6. **Proxy (procuration):** both vote types keep the same proxy flag/flow. The ballot consumes the
   represented company's voice.
7. **Confirmation + anti-double-vote:** every submitted vote has a review screen, then a unique
   timestamped reference number. Vote is final and irrevocable. A second attempt by the same company
   for the same vote/round is blocked.
8. **Voting window:** admin remotely activates/deactivates QR + voting window. Outside the active
   window, every new submission is rejected server-side.
9. **Secrecy + traceability:** each vote is timestamped and tied to the voting company for audit,
   but individual choices are not exposed to unauthorized third parties. Logs are admin-only.
10. **Results:** consolidated automatically at close, displayed in-room immediately for the closed
   vote; platform is read-only for that vote afterward; Excel + PDF reports are available after close.

### Board vote (`Election::TYPE_BOARD`)

This preserves the original CA election behavior.

- Candidates are scoped to the board vote (`candidates.election_id`).
- Each candidate must be tied to an AG company snapshot (`candidates.assembly_company_id`) so the
  displayed structure is historical and stable.
- Candidate photos are optional (`candidates.photo_path`) and stored on the Laravel `public` disk
  under `candidate-photos`.
- Two scrutin modes are auto-selected by candidate count:
   - **Mode A (> 20 candidates):** voter must select **exactly 20** candidates. Submit button is
     enabled *only* at exactly 20; block and warn if fewer/more. Show a live counter.
   - **Mode B (≤ 20 candidates):** all candidates are **automatically elected** — no ballot is shown;
     display the automatic-election result instead.
- Boundary ties in Mode A may launch a restricted runoff round. Votes are unique per
  `(election_id, company_id, round)`.

### Questions vote (`Election::TYPE_QUESTIONS`)

This is the newer vote type.

- A questions vote contains one or more `ElectionQuestion` rows.
- Each question has a title, optional detail text, and display order.
- The voter answers all questions in one grouped ballot, then confirms once.
- Allowed answers are **Oui**, **Non**, **Abstention**. Abstention is stored as `NULL` in
  `question_responses.answer`; Oui is `true`; Non is `false`.
- Results are displayed per question only. There is no aggregate winner for the vote as a whole.
- Main percentages are calculated on expressed votes only: `Oui + Non`. Abstentions are shown
  separately and can never win.
- Winner per question: Oui if Oui > Non, Non if Non > Oui, `Égalité` if Oui = Non, and
  `Aucun suffrage exprimé` if everyone abstains / no expressed vote exists.

## Architecture (intended)

Standard Laravel MVC. Expected core domains:

- **Assemblies (`Assembly`)** — parent object for one AG; owns votes and eligibility snapshots.
- **AG eligibility snapshot (`AssemblyCompany`)** — imported member list frozen per AG.
- **Votes (`Election`)** — one row per vote/scrutin inside an AG. `type` is `board` or `questions`.
  `active_slot = global` is the global activation lock.
- **Board candidates (`Candidate`)** — scoped to a board vote by `election_id`, tied to an
  `AssemblyCompany`, with an optional public photo.
- **Question votes (`ElectionQuestion`, `QuestionResponse`)** — grouped Oui/Non/Abstention ballots.
- **Auth/admin back-office** — login-protected; manages AGs, imports eligible companies per AG,
  creates votes, manages board candidates/questions, toggles QR/window, dashboards, results,
  Excel/PDF exports, and audit logs.
- **Voter flow** (public, QR-gated) — active vote resolution → eligibility check → type-specific
  ballot → review → submit → reference number. Board Mode B short-circuits to auto-election.
- **Election state** — multiple votes exist, but at most one vote is active globally.
- **Audit log** — append-only activity journal, admin-readable.

Enforce business rules **server-side**: active vote, AG eligibility snapshot, exact board selection
count, all question answers present, window-open, one-vote-per-company-per-vote/round. Client-side
Alpine/Tailwind is only for UX.

## Data model notes

- `assemblies`: AG metadata (`name`, `reference`, optional `held_on`).
- `companies`: global known company records.
- `assembly_companies`: per-AG snapshot with eligibility flags and `eligible`.
- `elections`: votes in an AG. Types: `board`, `questions`.
- `candidates`: board candidates; always scoped by `election_id`, required `assembly_company_id`,
  optional `photo_path`.
- `votes`: submitted ballot; contains `election_id`, `company_id`, `assembly_company_id`, `round`,
  proxy flag, reference, timestamp. Unique key: `(election_id, company_id, round)`.
- `vote_selections`: selected candidates for board ballots.
- `election_questions`: questions inside a questions vote.
- `question_responses`: Oui/Non/Abstention responses for questions.
- The app is not deployed yet; current migrations are direct target-schema migrations, not
  data-preserving backfills. Continue using one migration per table unless explicitly asked
  otherwise.

## Commands

The standard Laravel 13 workflow applies on this Windows machine (use `php` / `composer` as installed;
project Python uses `py` if any tooling needs it):

```bash
composer install
php artisan migrate            # add --seed to load seed data
php artisan serve              # local dev server
php artisan test               # full suite
php artisan test --filter=QuestionVoteTest      # single test class
php artisan test tests/Feature/VoterFlowTest.php   # single file
npm install && npm run dev     # Vite: Tailwind + Alpine assets
```

The app is scaffolded (Laravel 13.14, `artisan` present); these commands work as-is.

## Hard deadlines (from the TDR — drive prioritization)

- **11 Jun 2026** — platform deployed, tested, with admin + voter user guides.
- **13 Jun 2026** — test/training session with the EUROCHAM team.
- **17 Jun 2026** — final QR code generated and handed over.
- **18 Jun 2026** — AG day: on-site support + live result display.
- **+24h / +48h** — Excel+PDF results report / raw logs for archival.
