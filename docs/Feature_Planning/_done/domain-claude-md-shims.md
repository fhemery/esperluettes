# Domain CLAUDE.md shims

> WRAP output — the compact record of the finished task.

**Status:** DONE — 2026-08-12 · **Domain(s):** `dev` (agent skills / docs tooling)

## What it does

Every domain under `app/Domains/` now carries three documentation files instead
of two: `README.md` (human), `AGENTS.md` (agent instructions — Public API,
events catalogue, invariants) and `CLAUDE.md` (a one-line `@AGENTS.md` shim).
Claude Code auto-loads `CLAUDE.md` only, so before this an agent working inside
a domain got none of that domain's `AGENTS.md` unless told to read it. The shim
mirrors the pattern the repo root already used, minus root's Claude-Code-specific
addendum. The `document-domain` skill was updated to describe and produce all
three files, then the 26 shims were backfilled.

## Key behaviour

- **Shim content is fixed: exactly `@AGENTS.md`, one line, trailing newline,
  nothing else.** No heading, no domain name, no prose — identical in all 26
  files. A domain `CLAUDE.md` is generated output, not a third content file.
- **`AGENTS.md` is the agent-instructions file.** The skill's old wording called
  it "CLAUDE.md" while in fact writing `AGENTS.md` (26/26 domains proved it);
  that was corrected as terminology, not behaviour.
- **Root `CLAUDE.md` was not touched** — it was already a real file containing
  `@AGENTS.md` plus the Claude-Code editing rule. It is **not** a symlink, and
  never was.
- **Shim enforcement landed in a follow-up task.** At WRAP time nothing gated
  `CLAUDE.md`; see [`domain-docs-gate-check`](./domain-docs-gate-check.md),
  which added rules 4–5 to `scripts/check-docs.js` and documented Follow.

## Where the code lives

| Concern | Path |
|---------|------|
| Skill (single source; `.claude/skills/document-domain` is a dir symlink to it) | `.agents/skills/document-domain/SKILL.md` |
| Content contract for README vs AGENTS | `.agents/skills/document-domain/references/content-guide.md` |
| Claude Code agent shim | `.claude/agents/domain-documentor.md` |
| The 26 shims | `app/Domains/<Domain>/CLAUDE.md` |
| Domain Registry (26 rows, canonical list) | root `AGENTS.md` §"Domain Registry" |
| Gate docs check (rules 4–5 added in `domain-docs-gate-check`) | `scripts/check-docs.js` |

## Skill changes, concretely

`document-domain` went from a 5-step, two-file skill to a 6-step, three-file
one: Step 4 now targets `AGENTS.md`; a **new Step 5** writes the `CLAUDE.md`
shim (idempotent — no-op if the file already holds exactly that line); Verify
became Step 6 and gained a check that the shim is exactly `@AGENTS.md`.
References to "root `CLAUDE.md` Domain Registry" became root `AGENTS.md`
throughout both skill files.

## Decisions worth remembering

1. **No per-domain Claude addendum.** Root's "do not use Python/sed to edit
   files" rule is Claude-Code tooling guidance, not domain guidance, so it stays
   root-only. Reversible: add an addendum by hand in one domain's `CLAUDE.md`
   the day it is needed.
2. **Do not re-split agent content between `AGENTS.md` and `CLAUDE.md`.**
   `content-guide.md` now says this explicitly near the top; it exists to stop a
   future agent "helpfully" moving prose into the shim.
3. **No automated test.** Asserting a markdown file exists in PHPUnit would be
   test theatre. The proof is the shell assertion in `03-plan.md`'s phase
   acceptance (count shims, byte-compare each, check for orphans), run before
   and after. It printed `OK 26` with no orphans.
4. **VERIFY was skipped on purpose** (assumption #3): no route, controller,
   view, CSS, JS or DB change — nothing a browser could show. There is no
   screenshot pass and none should be invented later.

## Plan vs code

The code matches the plan; both phases shipped as planned in commits `4c022102`
and `7a94b506`. Two notes:

- `.agents/skills/wrap-task/SKILL.md` was a **deliberate one-line scope
  extension** beyond the functional spec's three-file list: it claimed root
  `CLAUDE.md` is a symlink to `AGENTS.md`, which is false. Corrected in phase 1.
- The functional spec's item 4 ("root `CLAUDE.md` — already correct, no change
  needed") held: root is untouched.

## Not done

Deliberate non-goals: no domain's `AGENTS.md`/`README.md` prose was rewritten;
no root content change; no other repo symlink touched.

Two pieces of drift this task found were filed and later shipped in
[`domain-docs-gate-check`](./domain-docs-gate-check.md): Follow documentation +
registry row, and gate enforcement of the trio + bidirectional registry sync.

No e2e specs were created by this task, so there was nothing to retire.
