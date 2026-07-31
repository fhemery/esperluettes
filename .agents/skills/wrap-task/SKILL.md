---
name: wrap-task
description: Close a finished feature — write the compact README, update the domain docs and the backlog. Use at the WRAP step of the loop, or when asked to summarise and close out a completed feature. Produces docs/Feature_Planning/<slug>/README.md, pushes leftovers back to the backlog and flags domain docs that need regenerating.
---

# Wrap the task

Two jobs: leave a record a future agent can load cheaply, and make sure nothing
that was dropped gets forgotten.

## 1. Establish what actually happened

Do not summarise the plan — summarise the code. Read:

- `git log` and the full diff since the task started;
- the phase index of `03-plan.md`, including phases not marked `DONE`;
- `DECISIONS.md`, including the assumptions table;
- the VERIFY report and the filled checklist.

Where the plan and the code disagree, the code is the truth. Say so explicitly:
"§3 of the architecture describes a listener that was not built, because …".

## 2. Write the compact README

`docs/Feature_Planning/<slug>/README.md` from
[`templates/README.md`](../../loop/templates/README.md).

This file exists so a finished feature costs ~120 lines of context instead of
~1000. It is what agents load by default; `01`–`03` stay as history behind
links. Optimise for the reader who is about to *change* this feature:

- the visibility and role rules, in one line each;
- the lifecycle rules;
- where the code lives, as a path table;
- the extension points used;
- the handful of decisions that would otherwise be re-litigated;
- the counter-intuitive bits.

Cut prose, never facts. No narrative of the implementation journey.

## 3. Record what was not done

In the "Not done" section, three separate things:

- deliberate non-goals (from §8 of the functional spec);
- anything cut mid-build, with the reason;
- open questions still open.

Then **push the ones worth doing back to `docs/Feature_Planning/BACKLOG.md`** as new
`TODO` rows with their own folder, and link them from the README. This is what
makes the loop a loop rather than a pipeline.

## 4. Retire the feature's e2e specs

Any spec this task left in `e2e/tests/features/` is now due. For each one,
**delete it** — the default, no justification needed — or **promote it to
`e2e/tests/core/`** if it guards something used across the app and breakable
from anywhere, writing the reason into the spec's header comment. Record the
decision in the README's "Not done" section.

`e2e/tests/core/` is the net that runs after every future feature, so it stays
small on purpose. A feature spec left behind is a bug in the process.

## 5. Update the surrounding docs

**A domain's docs must never reference `docs/Feature_Planning`.** Planning
documents are working memory for an in-flight task — they get renamed, split and
deleted when the task wraps, and a link from `app/Domains/**` into them rots. The
dependency runs one way only: planning may link to code docs, never the reverse.

So do not leave a pointer — **fold the content in**. `npm run gate` fails on a
violation. Three destinations, in order of preference:

1. **`app/Domains/<D>/README.md`** — anything a future reader of the domain
   needs: what is not done, a known drift, a decision that would otherwise be
   re-litigated. Stated in full, not linked.
2. **`app/Domains/<D>/Docs/`** — only when it is genuinely too long for the
   README, and only what cannot be learnt by reading the code. Link it from the
   README. Strip hard: a design document written before the code is planning
   material, redundant once the code exists.
3. **Delete it.** Most planning content earns neither of the first two. The
   folder under `docs/Feature_Planning/` is disposable by design; git keeps it.

A feature spanning several domains has no single home — put its record in the
domain that owns the core of it, not in a shared dumping ground.

- Touched domains: their `README.md` / `AGENTS.md` under `app/Domains/<D>/` may
  now be wrong. Regenerate with the `document-domain` skill, or flag them if you
  cannot.
- New domain, or a domain's responsibilities changed → the domain registry
  table in `AGENTS.md` (`CLAUDE.md` is a symlink to it — edit `AGENTS.md`).
- New notification type → `docs/notification-types.md`.
- New deptrac edge → make sure `02-architecture.md` §5 explains it.

## 6. Close the backlog row

- Move the row to the `## Done` table with the date, or set it to
  `BLOCKED:<reason>` if it is genuinely not finished.
- In `interactive` mode, **propose** this and let the user confirm; do not mark
  a task `DONE` on your own authority.

## 7. Commit the paperwork

The README, the backlog change and any regenerated domain docs go in one
`docs(<domain>)` commit. Follow the `commit` skill.

## 8. Report

Five to ten lines: what shipped, what did not, the new backlog rows created, the
docs that need attention, and the single thing you would tell the next person
touching this feature.
