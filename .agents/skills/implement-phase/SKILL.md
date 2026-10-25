---
name: implement-phase
description: Implement one phase of a feature's implementation plan, test-first, until the gate is green. Use at the BUILD step of the loop — one invocation per phase of the plan in docs/Feature_Planning/<slug>/CONTEXT.md. Writes failing integration tests first, implements the minimum that passes, runs pnpm run gate, and updates the phase status.
---

# Implement one phase

You implement **exactly one phase** of the plan in `CONTEXT.md` (§3). Not the
next one, not a bit of the next one.

## Read first, in this order

`CONTEXT.md` is one file but you do not read it whole — read these parts only:

1. **§0 Handoff** — what the previous agents want you to know.
2. **§3 — your phase and the phase index only.** Your phase is self-contained
   by design; do not read the other phases' bodies.
3. **§4 Decisions** — every settled question. Never re-decide one of these.
4. **§2 Architecture** — only the subsections your phase names.
5. **§1 Functional spec** — only when a behaviour is ambiguous.
6. The nearest existing implementation in the codebase. Match it.

Use offset/limit or grep on the `## ` headings; your context is finite and you
carry everything you open until the phase ends.

You cannot ask the user anything. If the phase is genuinely underspecified, do
the part that is clear, stop, and report precisely what is blocking. Do not
guess on a security or privacy rule — ever.

## Work test-first

1. Write the phase's tests from the "Tests" section. They must fail for the
   right reason — run them and read the failure.
2. Implement the minimum that makes them pass. Nothing speculative: no config
   flag, no abstraction, no "while I'm here" refactor. Rule #2 and #3 of
   `AGENTS.md`.
3. Re-run. Then run the full gate.

Integration tests are the default: hit the real route, as a real user with a
real role, and assert on the response and the database. Test the **denial**
paths as hard as the happy path — a private field must be absent from the
response body for a non-owner, not merely hidden by Blade.

## Project rules that break builds

- Code only under `app/Domains/<Domain>/`; layout per `docs/Domain_Structure.md`.
- Controllers → services → models. Controllers never touch models.
- Form requests for validation.
- **No `PATCH` routes.** The production WAF blocks the verb. Use `PUT`.
- Models use PHP attribute syntax (`#[Table]`, `#[Fillable]`, `#[Hidden]`);
  `$casts` stays a property.
- Migrations: `YYYY_MM_DD_HHiiss_name`, always a `down()`, indexes on searched
  columns, foreign keys only within the owning domain.
- Every user-visible string in a French lang file.
- Eager-load to avoid N+1.
- PSR-12.

## The gate

**Never let a test or gate run print into your context.** A green run is worth
one word; a red one is worth its failures. Redirect, then read only what broke:

```bash
pnpm run gate > /tmp/gate.log 2>&1 && echo GATE_GREEN || tail -40 /tmp/gate.log
pnpm run gate --quick > /tmp/gate.log 2>&1 && echo GATE_GREEN || tail -40 /tmp/gate.log
./vendor/bin/sail artisan test --filter=X > /tmp/test.log 2>&1 && echo PASS || tail -30 /tmp/test.log
```

`--quick` skips the asset build while iterating; `--only=php` narrows further.
Grep the log for a specific failure rather than re-running the suite — the log
is still on disk and costs nothing to search.

Run the full gate before declaring the phase done. On a deptrac violation, use
the `fix-deptrac` skill — and remember that adding an edge to `deptrac.yaml` is
an architecture decision: if `02-architecture.md` did not anticipate it, report
it rather than deciding alone.

(Throughout, "`02-architecture.md`" means §2 of `CONTEXT.md`.)

If the gate fails twice for the same reason, stop and report. Do not disable a
test, loosen an assertion, or add a deptrac exception to get green.

Failures that are not your branch's — check these before debugging your code:

- **Dozens of PHP tests 500 with `Unable to locate file in Vite manifest`.**
  The gate skipped the asset build and PHP tests rendered against a stale
  `public/build/manifest.json`. Run `pnpm run gate --all` once to rebuild.
- **Only `EditorAssetsTest` (asset counts) fails.** A leftover `public/hot`
  points `@vite` at a dead dev server. If `curl $(cat public/hot)/@vite/client`
  gets no answer, delete `public/hot` (gitignored) and re-run.

Deptrac does not analyse Pest test files — its collectors are class-based and
a Pest file declares no class. A test may use another domain's public API or
constants when that is the clearest way to write it; production code under
`Public/`/`Private/` is fully checked.

## Related skills

Use them rather than reconstructing the procedure:

- `add-event` — a new domain event and its consumers
- `add-notification` — a new user notification
- `add-setting` — a new user preference
- `fix-deptrac` — a deptrac violation
- `commit` — the project's commit conventions

## Finish

1. Tick the phase to `DONE` in the phase index (§3.1) and add its line to §5.
2. Append to §4 anything you had to decide.
3. Add a §0 Handoff entry (`BUILD phase N → next`): only what the next phase
   or WRAP cannot learn from the code and the plan — a trap, a deviation from
   the plan, something deliberately left out.
4. Commit the phase — one phase, one commit. Follow the `commit` skill.
5. Report: what you built, the tests that prove it, the gate result, anything
   that contradicted the plan, and anything you deliberately left out.

Report failure plainly if the phase is not finished. A phase reported `DONE`
that is not green poisons every phase after it.
