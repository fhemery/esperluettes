# Domain docs gate check

> WRAP output — the compact record of the finished task.

**Status:** DONE — 2026-08-13 · **Domain(s):** `dev` (gate tooling) · **Follow** (documented)

## What it does

The gate docs step (`pnpm run gate` → `docs` → `scripts/check-docs.js`) now
fails when any immediate subdirectory of `app/Domains/` is missing the
documentation trio (`README.md`, `AGENTS.md`, `CLAUDE.md`), when `CLAUDE.md`
lacks a line `@AGENTS.md`, or when disk and the root `AGENTS.md` Domain
Registry drift apart (missing row or orphan row). This closes the hole left by
[`domain-claude-md-shims`](./domain-claude-md-shims.md): new domains could ship
without docs or a registry entry and stay green. Follow was the only gap on
disk — it was documented and registered in phase 1 so the new rules pass on a
clean tree.

## Key behaviour

- **Domains = immediate subdirs of `app/Domains/`.** Files at that root are
  ignored; discovery is filesystem-first, not registry-first.
- **`CLAUDE.md` must include a line `@AGENTS.md`.** Extra Claude-Code-only
  instructions are allowed (same pattern as root `CLAUDE.md`). The gate does
  not require a byte-exact one-line shim.
- **Registry check is bidirectional.** Every disk domain needs Path
  `` `app/Domains/<Name>` `` in the Domain Registry table; every such Path must
  point at an existing directory. Responsibilities / Tables columns are not
  validated.
- **All five checks run every time; non-fail-fast.** Violations aggregate per
  check label, then the script exits 1. Existing rules (Feature_Planning ban in
  domain docs and ADRs, relative-link resolution) are unchanged.
- **No PHPUnit / no Vitest for the docs checker.** The gate docs step *is* the
  test: it runs against the real tree every `pnpm run gate`.

## Where the code lives

| Concern | Path |
|---------|------|
| Docs step (rules 1–5) | `scripts/check-docs.js` |
| Follow documentation trio | `app/Domains/Follow/README.md`, `AGENTS.md`, `CLAUDE.md` |
| Domain Registry row | root `AGENTS.md` § Domain Registry (**Follow**) |

## Extension points used

N/A — developer tooling only. Follow docs describe its real registries
(ProfileTabRegistry, NotificationFactory, SettingsPublicApi) but this task did
not add new registry hooks.

## Decisions worth remembering

1. **Extend `check-docs.js`, not a new gate step** — one always-on docs module
   (same as Feature_Planning ban and link checks).
2. **Follow documented in-task, not allowlisted** — excluding it would leave
   the filed drift open.
3. **Registry parser reads the `## Domain Registry` markdown table** — Path
   cells must stay backtick-wrapped `` `app/Domains/<Name>` ``; a table redesign
   breaks parsing.
4. **VERIFY skipped** — no browser surface (same rationale as
   `domain-claude-md-shims`).
5. **`CLAUDE.md` is not byte-locked** — must contain `@AGENTS.md` as a line;
   Claude-Code-only addenda are allowed (supersedes the stricter WRAP wording
   from `domain-claude-md-shims`).

## Plan vs code

All three BUILD phases shipped as planned (`2951ab05`, `f726e560`, `2f0306d6`).
Post-WRAP: the colocated Vitest suite was removed (duplicated the gate), and
the CLAUDE check was relaxed from byte-exact shim to "includes `@AGENTS.md`".

## Not done

- **Deliberate non-goals (functional §8):** no README/AGENTS prose quality
  checks; no husky-only or separate CI job; no changed-domain scoping of the
  docs step; no Responsibilities/Tables column validation.
- **Nothing cut mid-build.**
- **No open questions.**
- **No new backlog rows** — scope was fully closed.
- **No e2e specs** were created; nothing to retire or promote.

## Resolved from `domain-claude-md-shims`

That WRAP's "Not done" filed two items here; both shipped:

- Follow is documented and in the Domain Registry.
- The gate enforces the three-file rule and registry sync.
