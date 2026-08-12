# Domain CLAUDE.md shims — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Fix `document-domain` terminology and add the shim step | S | — | DONE |
| 2 | Backfill the 26 domain `CLAUDE.md` shims | S | 1 | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

**Why two phases and not one.** The work is docs-only and small enough that one
phase would be defensible. It is cut in two because the two halves are different
kinds of work with different review needs: phase 1 is prose judgment across three
files, phase 2 is 26 byte-identical generated files that would otherwise bury the
prose diff. Ordering matters too — phase 1 writes down the shim rule, phase 2
executes it, so phase 2 doubles as the first exercise of the newly documented
step. Each ends green and reverts on its own.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation. **This task has no automated test
  level** (see "Testing note" below) — the substitute is the shell check named in
  each phase's Acceptance, run *before* the change to see it fail and *after* to
  see it pass.
- We do not move to phase 2 until phase 1's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

## Testing note — read before either phase

There is no PHP or Vitest test to write here. Nothing in this task touches PHP,
JS, routes, models, tables or UI: it edits three markdown/skill files and creates
26 one-line markdown files. Writing a PHPUnit test that asserts a markdown file
exists would be test theatre against the project's "minimum code, nothing
speculative" rule.

What replaces it:

1. `npm run gate` — the `docs` step (`scripts/check-docs.js`) must stay green.
   Confirmed by reading that script: `checkPlanningReferences` only inspects
   files basenamed `README.md` or `AGENTS.md`, and `checkRelativeLinks` only
   matches `[text](path)` syntax. A `CLAUDE.md` containing `@AGENTS.md` is
   invisible to both — so "green" here means "we broke nothing", not "the shims
   are correct".
2. A shell assertion, given verbatim in each phase's Acceptance, which is what
   actually proves the shims are correct.

Run the gate per the loop's context discipline — never let its full output land
in the transcript:

```bash
npm run gate > /tmp/gate.log 2>&1 && echo GATE_GREEN || tail -40 /tmp/gate.log
```

`npm run gate -- --only=docs` is enough for the inner loop; run the full gate
once before declaring a phase done.

---

## Phase 1 — Fix `document-domain` terminology and add the shim step

**Goal.** Make the `document-domain` skill and its agent shim describe the three
files that actually exist — `README.md` (human), `AGENTS.md` (agent
instructions), `CLAUDE.md` (one-line shim) — instead of the stale two-file
`README.md` + `CLAUDE.md` story.

**Context you need.** See `architecture §"Skill changes"` and
`§"The shim content"`. The key fact, already verified during PLAN and not worth
re-checking: **all 26 documented domains carry `AGENTS.md` and none carries
`CLAUDE.md`.** So every place the skill says "CLAUDE.md" while describing Public
API / events / invariants content is a stale label for the file the skill already
writes as `AGENTS.md`. This is a terminology fix, not a behaviour change
(`DECISIONS.md` assumption #2). Do **not** rewrite the content contract itself —
the guidance about what belongs in the agent file is correct as written; only the
filename it is attached to is wrong.

**Deliverables.**

1. `.agents/skills/document-domain/SKILL.md`
   - Frontmatter `description`: today it says *"Produces or updates both
     README.md and CLAUDE.md"*. It must name the three outputs. Keep the natural
     trigger phrases (a user will still say "write the CLAUDE.md for X") but make
     the produced-files clause accurate.
   - Intro list (currently two bullets, `README.md` / `CLAUDE.md`): three bullets
     — `README.md` human overview, `AGENTS.md` agent instructions, `CLAUDE.md`
     one-line shim so Claude Code auto-loads `AGENTS.md`.
   - Step 1: *"match it against the Domain Registry in the root `CLAUDE.md`"* →
     root `AGENTS.md`. The registry table lives at `AGENTS.md` §"Domain Registry"
     (verified during PLAN); root `CLAUDE.md` only reaches it via `@AGENTS.md`.
   - Step 2 item 2: `Existing CLAUDE.md (if present)` → `Existing AGENTS.md`.
   - Step 4 heading, target path and closing sentence: `CLAUDE.md` → `AGENTS.md`
     throughout (`Target: app/Domains/<Domain>/AGENTS.md`, *"If an AGENTS.md
     already exists, merge and update rather than overwrite"*).
   - **New Step 5 — "Write the CLAUDE.md shim"**, inserted between the current
     Step 4 and Step 5, renumbering Verify to Step 6. Content: write
     `app/Domains/<Domain>/CLAUDE.md` containing exactly the single line
     `@AGENTS.md`. State that it is idempotent (if the file already holds exactly
     that line, no-op), that it must carry no other prose, and why it exists
     (Claude Code auto-loads `CLAUDE.md` only; the shim gives it `AGENTS.md`
     without duplicating a word). Mirror the root `CLAUDE.md` pattern, minus
     root's Claude-Code-specific addendum — that rule is about Claude Code
     tooling, not about any domain, so it stays root-only
     (`DECISIONS.md` assumption #1).
   - Verify step: item 1 `CLAUDE.md` → `AGENTS.md`; item 3 *"root CLAUDE.md
     Domain Registry"* → root `AGENTS.md`; add an item checking the shim is
     exactly `@AGENTS.md`.

2. `.agents/skills/document-domain/references/content-guide.md`
   - Title and core principle: *"README.md vs CLAUDE.md"* → *"README.md vs
     AGENTS.md"*; `**CLAUDE.md** = agent instructions` → `**AGENTS.md** = agent
     instructions`; *"The test for CLAUDE.md"* → *"for AGENTS.md"*.
   - `## CLAUDE.md` section heading, `### Never include in CLAUDE.md`, the worked
     example's `### CLAUDE.md would contain` / `### CLAUDE.md would NOT contain`,
     and the calibration checklist's `**CLAUDE.md**` heading → `AGENTS.md`.
   - The "Never include" table row *"Table names | Already in root CLAUDE.md
     Domain Registry"* → root `AGENTS.md` Domain Registry.
   - Add a short note (top of the file, right after the core principle) stating
     that `CLAUDE.md` in a domain is a **generated one-line shim** containing
     `@AGENTS.md` and nothing else — not a third content file to calibrate, and
     never a place to put prose. This is the note `architecture §"Skill changes"`
     asks for; it is the thing that stops a future agent re-splitting content
     between `AGENTS.md` and `CLAUDE.md`.
   - Do not touch the README guidance sections, the `Docs/` guidance, or the
     `Feature_Planning` rule — all still correct.

3. `.claude/agents/domain-documentor.md`
   - The task line *"use the document-domain skill in .claude/skills/document-domain
     to update the README.md and the CLAUDE.md files"* → name the three outputs
     (`README.md`, `AGENTS.md`, and the one-line `CLAUDE.md` shim). Leave the
     frontmatter, examples and tool list alone.

4. `.agents/skills/wrap-task/SKILL.md` — **one line, deliberate scope extension.**
   Line ~91 reads: ``the domain registry table in `AGENTS.md` (`CLAUDE.md` is a
   symlink to it — edit `AGENTS.md`)``. Root `CLAUDE.md` is **not** a symlink; it
   is a real file containing `@AGENTS.md` plus a Claude-Code editing rule
   (verified during PLAN with `od -c`). Correct the parenthetical to say root
   `CLAUDE.md` includes `AGENTS.md` via `@AGENTS.md`. Rationale for going one
   file beyond `01-functional.md`'s scope list: it is a factually false statement
   about the exact mechanism this task establishes, and leaving it would send a
   future WRAP agent looking for a symlink that does not exist. Nothing else in
   `wrap-task/SKILL.md` may be touched. Recorded in "Open items" so WRAP surfaces
   it.

**Note on `.claude/skills/document-domain`.** It is a directory symlink to
`.agents/skills/document-domain/` (`architecture §"Placement"`). Edit the
`.agents/` copy only — there is nothing to mirror.

**Tests.** None automated; see "Testing note" above. The proof is the Acceptance
grep, run before and after.

**Acceptance.**

- ✅ No occurrence of `CLAUDE.md` remains in `document-domain` or the agent shim
  except ones that are genuinely about the shim file or root:

  ```bash
  grep -rn 'CLAUDE\.md' .agents/skills/document-domain/ .claude/agents/domain-documentor.md
  ```

  Every hit must be a sentence describing the one-line shim (or the trigger
  phrases in the SKILL frontmatter `description`). No hit may still present
  `CLAUDE.md` as the file holding Public API / events / invariants content.
- ✅ `.agents/skills/document-domain/SKILL.md` contains a step whose instruction
  is to write `app/Domains/<Domain>/CLAUDE.md` with exactly `@AGENTS.md`, and its
  steps are numbered contiguously after the insertion.
- ✅ `content-guide.md` states, near the top, that a domain `CLAUDE.md` is a
  generated one-line shim and not a content file.
- ✅ `grep -n 'symlink' .agents/skills/wrap-task/SKILL.md` no longer claims root
  `CLAUDE.md` is a symlink to `AGENTS.md`.
- ✅ No file under `app/Domains/` was modified by this phase:
  `git status --porcelain app/Domains` is empty.
- ✅ `npm run gate` green.

---

## Phase 2 — Backfill the 26 domain `CLAUDE.md` shims

**Goal.** Every documented domain under `app/Domains/` gets a `CLAUDE.md`
containing exactly `@AGENTS.md`, so a Claude Code agent working in that domain
auto-loads the domain's `AGENTS.md`.

**Context you need.** See `architecture §"The shim content"` and `§"Backfill"`.
Phase 1 has already updated the `document-domain` skill to describe this shim as
one of its outputs; this phase applies that rule retroactively to the domains
that exist today. The content is fixed and identical across all 26 files — there
is **no per-domain judgment**, no per-domain wording, and nothing to read inside
any domain. If you find yourself opening a domain's `AGENTS.md` to decide what to
write, stop: the answer is always the same one line.

**Deliverables.** 26 new files, `app/Domains/<Domain>/CLAUDE.md`, each containing
exactly:

```
@AGENTS.md
```

One line, trailing newline, nothing else — no heading, no comment, no domain
name.

The 26 domains, which are exactly the directories under `app/Domains/` that carry
an `AGENTS.md` (verified during PLAN):

Administration, Auth, Calendar, Comment, Config, Dashboard, Discord, Editor,
Events, FAQ, Home, Media, Message, Moderation, News, Notification, Profile,
Quote, ReadList, Search, Settings, Shared, StaticPage, Statistics, Story,
StoryRef.

**`app/Domains/Follow/` is excluded and this is intentional.** It is a 27th
directory on disk, but it has no `AGENTS.md`, no `README.md`, and no row in the
root `AGENTS.md` Domain Registry — it is an undocumented domain. A `CLAUDE.md`
there would `@`-include a file that does not exist. Documenting `Follow` is a
separate task; do not create its shim and do not write its `AGENTS.md` here. See
"Open items".

**Bulk-edit exemption.** `CLAUDE.md` §"Editing files" bans scripting file
modifications, with an explicit carve-out for changes that are "genuinely
mechanical across many files", provided you say so before doing it. This is that
case: 26 byte-identical new files. Say so, then either use the Write tool 26
times or a single loop — both are acceptable here. Do not use scripting for
anything else in this phase.

**Tests.** None automated; see "Testing note" above.

**Acceptance.**

- ✅ Exactly 26 shims exist, one per documented domain, each byte-exact. This
  command must print `OK 26` and nothing else:

  ```bash
  n=0; bad=0
  for d in $(find app/Domains -maxdepth 2 -name AGENTS.md -printf '%h\n' | sort); do
    n=$((n+1))
    [ "$(cat "$d/CLAUDE.md" 2>/dev/null)" = "@AGENTS.md" ] || { echo "BAD: $d"; bad=1; }
  done
  [ "$bad" = 0 ] && echo "OK $n"
  ```

- ✅ No shim was created for an undocumented domain — this prints nothing:

  ```bash
  find app/Domains -maxdepth 2 -name CLAUDE.md -printf '%h\n' \
    | while read -r d; do [ -f "$d/AGENTS.md" ] || echo "ORPHAN: $d"; done
  ```

- ✅ `app/Domains/Follow/CLAUDE.md` does not exist.
- ✅ The phase adds only new files: `git status --porcelain app/Domains` shows 26
  entries and every one is untracked/added (`??` or `A`). No existing
  `README.md` or `AGENTS.md` was modified.
- ✅ Root `CLAUDE.md` is unchanged (`git diff --exit-code CLAUDE.md` clean) — it
  is already correct and is explicitly out of scope per `01-functional.md`.
- ✅ `npm run gate` green — in particular the `docs` step, confirming the new
  files are inert to `check-docs.js` as predicted in
  `architecture §"Deptrac / gate impact"`.

---

## Visual QA checklist

**Not applicable — VERIFY should be skipped for this task.**

This is a documentation-and-tooling change with no user-facing surface: no route,
no controller, no Blade view, no CSS, no JS, no database change. There is nothing
a browser can show that would differ before and after. Inventing a screenshot
pass here would produce a folder of images proving only that unrelated pages
still render.

| Surface | Check | OK? |
|---------|-------|-----|
| _(none)_ | No user-facing surface is touched; the gate's `docs` step plus the per-phase shell assertions are the complete verification. | n/a |

This matches `DECISIONS.md` assumption #3 (no VERIFY step). If BUILD ends up
touching anything user-facing — it should not — that assumption is void and the
decision must be reopened rather than quietly skipped.

## Open items

- **`.agents/skills/wrap-task/SKILL.md` is a scope extension.** `01-functional.md`
  lists three files to edit plus the backfill; phase 1 edits a fourth, for the
  single false claim that root `CLAUDE.md` is a symlink to `AGENTS.md` (verified
  false during PLAN). Needed by: **phase 1**. It is a one-line factual
  correction and no user was available to arbitrate (`auto` mode), so it is being
  taken rather than deferred — but WRAP must surface it as an assumption, and it
  is trivially revertable on its own.

- **`app/Domains/Follow/` is an undocumented 27th domain.** It has no
  `AGENTS.md`, no `README.md`, and no Domain Registry row, so it gets no shim
  (phase 2 excludes it explicitly). This is pre-existing drift that this task
  discovered but does not fix. Needed by: **phase 2** — only as a boundary, no
  action required. Worth a follow-up backlog entry: run `document-domain` on
  `Follow` and add its registry row; the updated skill will then create its shim
  as a matter of course.

- **Nothing enforces the shims mechanically after this task.** `check-docs.js`
  does not know about `CLAUDE.md`, so a future domain that gets an `AGENTS.md`
  without a `CLAUDE.md` will drift silently, exactly as `Follow` drifted. The
  design deliberately relies on the `document-domain` skill (phase 1) to write
  the shim each time it runs — that is the recorded scope in
  `01-functional.md`, and this plan does **not** add a gate rule. Surfacing the
  tradeoff per critical rule #1: a ~10-line Rule 3 in `scripts/check-docs.js`
  ("every `app/Domains/*/AGENTS.md` has a sibling `CLAUDE.md` containing
  `@AGENTS.md`") would close it permanently and would be cheap. Needed by: no
  phase — it is a deliberate non-goal here, raised for the user to accept or
  schedule as a follow-up.
