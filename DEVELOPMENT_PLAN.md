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

- [ ] QR landing → eligibility form (name, first name, member company, optional proxy company).
- [ ] Eligibility check against imported list; explicit "contact secretariat" message on failure.
- [ ] Reject if voting window closed or company already voted.
- [ ] **Mode A** ballot: full candidate list, live counter, submit enabled only at exactly 20.
- [ ] **Mode B**: skip ballot, show auto-election result.
- [ ] Review screen → final submit → unique timestamped reference number (final & irrevocable).
- [ ] Block + warn on any second attempt by the same company.

## Phase 4 — Results & proclamation

- [ ] Auto-consolidate results at window close; in-room display view.
- [ ] Read-only public results mode after AG.
- [ ] Excel + PDF official report generation.

## Phase 5 — Security, performance, QA

- [ ] HTTPS/SSL-TLS enforced; CSRF on all forms; hash sensitive data.
- [ ] Redis-backed sessions + locking to guarantee anti-double-vote under concurrency.
- [ ] Load test for **150+ concurrent users** within the 30-min window.
- [ ] Feature tests for each critical rule: eligibility, exactly-20 (Mode A), Mode B auto-election,
      window open/closed, one-vote-per-company, reference-number generation.
- [ ] Acceptance/test session with EUROCHAM (13 Jun); admin + voter user guides.

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
