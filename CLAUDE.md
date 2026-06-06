# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Electronic voting platform for **EUROCHAM Sénégal**'s Annual General Assembly — election of the
2026 Board of Directors (Conseil d'Administration), held **18 June 2026** with a single **30-minute**
voting window. Voters access the ballot by scanning a QR code in the room (no app install). The full
technical proposition (source of truth for requirements) is
`Proposition_Technique_EUROCHAM_CheikhDiagne.docx`; the phased build is in
[`DEVELOPMENT_PLAN.md`](./DEVELOPMENT_PLAN.md).

Reference: `P01.EUROCHAM.2026`. The document and election are in **French** — keep all voter- and
admin-facing UI strings in French.

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

## Critical electoral rules (correctness-critical — verify against the proposition before changing)

These rules define a real institutional election. Getting them wrong is a correctness failure, not a
style issue.

1. **One member company = one vote** (`voix unique`). Identity = the member company, not the person.
2. **Eligibility** is checked against an Excel/CSV list the admin imports beforehand. A company is
   eligible if up to date on: annual survey 2025, OR annual dues 2025, OR (for new members) entry
   fees + 2026 dues. Unknown/ineligible company → explicit message to contact the secretariat.
3. **Proxy (procuration):** a voter may also represent one other member company by proxy — capture
   the represented company's name when present.
4. **Two scrutin modes, auto-selected by candidate count:**
   - **Mode A (> 20 candidates):** voter must select **exactly 20** candidates. Submit button is
     enabled *only* at exactly 20; block and warn if fewer/more. Show a live counter.
   - **Mode B (≤ 20 candidates):** all candidates are **automatically elected** — no ballot is shown;
     display the automatic-election result instead.
5. **Confirmation + anti-double-vote:** show a review screen before final submit; after submit show a
   unique **timestamped reference number**. Vote is **final and irrevocable** — never editable. A
   second vote attempt by the same company is blocked with an explicit message.
6. **Voting window:** admin remotely activates/deactivates the QR code / ballot. Outside the official
   window, every new submission is rejected by the system.
7. **Secrecy + traceability:** each vote is stored with a timestamp tied to the voting company, but
   individual choices are not exposed to unauthorized third parties. Logs/traceability are
   **admin-only**, for audit or dispute.
8. **Results:** consolidated automatically at close, displayed in-room immediately; platform stays
   **read-only** afterward; Excel + PDF report delivered within 24h.

## Architecture (intended)

Standard Laravel MVC. Expected core domains:

- **Auth/admin back-office** — login-protected; imports eligible companies, manages candidates,
  generates/toggles the QR code, opens/closes the voting window, live participation dashboard,
  per-candidate results, Excel/PDF export, scrutin-mode parameter.
- **Voter flow** (public, QR-gated) — eligibility check → ballot (Mode A) → review → submit →
  reference number. Mode B short-circuits to the auto-election result.
- **Election state** — a single configurable election/scrutin (window open/closed, mode, candidate
  list) that gates the voter flow.
- **Audit log** — append-only activity journal, admin-readable.

Enforce business rules **server-side** (eligibility, exactly-20, window-open, one-vote-per-company);
client-side Alpine/Tailwind is only for UX (live counter, disabled submit).

## Commands

The standard Laravel 13 workflow applies on this Windows machine (use `php` / `composer` as installed;
project Python uses `py` if any tooling needs it):

```bash
composer install
php artisan migrate            # add --seed to load seed data
php artisan serve              # local dev server
php artisan test               # full suite
php artisan test --filter=VoteEligibilityTest   # single test class
php artisan test tests/Feature/VotingModeTest.php   # single file
npm install && npm run dev     # Vite: Tailwind + Alpine assets
```

The app is scaffolded (Laravel 13.14, `artisan` present); these commands work as-is.

## Hard deadlines (from the TDR — drive prioritization)

- **11 Jun 2026** — platform deployed, tested, with admin + voter user guides.
- **13 Jun 2026** — test/training session with the EUROCHAM team.
- **17 Jun 2026** — final QR code generated and handed over.
- **18 Jun 2026** — AG day: on-site support + live result display.
- **+24h / +48h** — Excel+PDF results report / raw logs for archival.
