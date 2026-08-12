# Fix audit vulnerabilities

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-12 · **Domain(s):** `dev` (lockfiles only)

## Accepted exception — Quill XSS (read this first)

**`quill@2.0.3`** (exact pin in `package.json`) remains the **sole accepted NPM
audit advisory**: [GHSA-v3m3-f69x-jf25](https://github.com/advisories/GHSA-v3m3-f69x-jf25)
(low severity — XSS via HTML export in the editor).

- **Why not fixed:** upstream's latest release *is* the vulnerable version; `npm
  audit fix --force` would **downgrade to 2.0.2**, which we rejected.
- **Mitigation in app:** server-side HTML sanitisation on rendered editor output
  (unchanged by this task).
- **When to revisit:** upstream ships ≥2.0.4, or a real exploit path bypasses
  sanitisation, or we replace Quill.
- **Documentation location:** this file only — not a `package.json` comment
  (DECISIONS #8).

After BUILD, `npm audit` shows **no fixable** findings; prod-only
(`npm audit --omit=dev`) may still list Quill only.

## What it does

Maintainer chore: cleared all fixable Composer and NPM dependency audit
findings by bumping locked package versions. No application domain code, UI,
routes, or Quill behaviour changed. Success criteria were clean
`composer audit`, clean `npm audit` except Quill, and green `npm run gate`.

## Key behaviour

- **Composer:** targeted `league/commonmark` 2.8.3 → **2.10.0** within Laravel's
  existing `^2.8.1` constraint; `./vendor/bin/sail composer audit` → 0 advisories.
- **NPM:** `npm audit fix` (no `--force`) patched `brace-expansion`, `fast-uri`,
  `js-yaml`, `nanoid`, `postcss` in the lockfile only.
- **Unchanged:** `composer.json`, `package.json` direct ranges/pins (including
  exact `quill@2.0.3`, `axios`, Alpine).
- **VERIFY:** skipped (N/A) — no user-facing surface to smoke-test.

## Where the code lives

| Concern | Path |
|---------|------|
| PHP lockfile | `composer.lock` — `league/commonmark@2.10.0` |
| JS lockfile | `package-lock.json` — patched transitive dev deps |
| Quill pin (exception) | `package.json` → `"quill": "2.0.3"` |
| Editor consumer | `app/Domains/Editor/` — unchanged; uses Quill via components |

Commits: `1ad4fd93` (Composer), `ec403885` (NPM). No migrations, tests, routes,
or `app/Domains/*` edits.

## Extension points used

None.

## Decisions worth remembering

- **Quill:** accept GHSA-v3m3-f69x-jf25; no downgrade to 2.0.2 (#1).
- **Minimal bumps only** — no drive-by majors, no axios unpin (#2, #4).
- **Audits not in CI/gate** — fix findings only; enforcement is a separate chore
  (#3) → `dependency-audit-gate/` backlog row.
- **Composer:** targeted `league/commonmark` update, not full `composer update`
  (#5).
- **NPM:** `npm audit fix` without `--force`; no `overrides` unless fix fails (#6).
- **Assumptions (auto mode):** all nine rows in the archived decisions log were
  applied as stated — notably targeted commonmark only, WRAP-only Quill docs,
  VERIFY skipped.

## Plan vs. code

Both phases shipped as planned. No drift: lockfile-only diffs, gate green after
each commit. Minor incidental: `composer.lock`'s `plugin-api-version` metadata
moved 2.6.0 → 2.9.0 alongside the commonmark bump (not called out in the plan,
gate-irrelevant).

## Not done

- Deliberate non-goals: audit enforcement in CI/`gate`, Dependabot/Renovate,
  Quill replacement, broad dependency refreshes, Laravel/PHP majors.
- Nothing cut mid-build.
- Open follow-up: upstream Quill patch ≥2.0.4 — prefer upgrading over keeping
  the exception (non-blocking note from REFINE).
- Pushed to backlog: `dependency-audit-gate/` — add `composer audit` + `npm audit`
  (honouring the Quill exception) to gate or CI. See [`BACKLOG.md`](../BACKLOG.md).
- No feature e2e specs to retire (none added). VERIFY N/A.
