# Gate — scoped test paths — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.
>
> Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Not a product domain. Ownership sits on the existing Artisan wrapper
`App\Console\Commands\ParallelTestCommand` (`test:parallel`). The bug is that
its signature already advertises `{dirs?*}` while the implementation forwards
every positional argument into Collision/`artisan test --parallel`, and
ParaTest accepts only one optional `path`.

`scripts/gate.js` keeps spreading `phpPlan.dirs` into a single
`test:parallel …dirs` invocation — that call shape is correct once the command
honours it. No domain under `app/Domains/` changes.

### 1.1 Changes in other domains

None.

## 2. Data model

N/A — no tables, models, or lifecycle.

## 3. PHP architecture

### 3.1 Public API

N/A for domains. The contract is the Artisan command:

```
php artisan test:parallel                          → full suite, parallel
php artisan test:parallel <dir>                    → that dir, parallel
php artisan test:parallel <dir1> <dir2> …          → each dir, parallel, in turn
```

Exit code: `0` only if every invocation succeeds; otherwise the first
non-zero (or the worst — PLAN picks one; either is fine for the gate).

### 3.2 Services / policy / events / routes

N/A.

### 3.3 Command behaviour (contract)

- Resolve process count as today (`TEST_PROCESSES` or ~80% of cores).
- **Zero path args:** one delegated `artisan test --parallel --processes=N`
  (unchanged; CI and `-- --all` stay on this path).
- **One or more path args:** for each directory argument, one delegated
  `artisan test --parallel --processes=N <that-dir>` with argv rewritten the
  same way the command already does for a single path. Do not pass more than
  one path into ParaTest in a single call.
- Non-path CLI flags (if any appear after the command name today) keep working
  for the zero- and one-dir cases; multi-dir must not invent new flag syntax.
- `scripts/gate.js` is unchanged for the PHP step.

ParaTest’s single-`path` limit is accepted as an upstream constraint; the
wrapper loops rather than fighting Collision/ParaTest.

## 4. Frontend architecture

N/A. Optional: no change to `gate.js` labels beyond what already shows
`PHP tests (N domains)`.

## 5. Deptrac

No new edges. Console command stays outside domain layers.

## 6. Testing strategy

- **Feature / console test** (preferred): drive `artisan test:parallel` with
  two known-good tiny paths (or two real domain `Tests` dirs that are empty of
  failures) and assert exit `0` — plus a regression that two args no longer
  produce the Symfony “Too many arguments… path” message.
- Reproducing the bug without a full suite: calling the command with two
  directory strings that exist is enough; the failure today is argv validation,
  not test discovery.
- **Gate smoke:** not required as an automated test of `gate.js`; BUILD
  acceptance is a scoped gate on a tree that would resolve ≥2 dirs (or an
  equivalent direct `test:parallel` with two dirs) plus `-- --all` still green.
- VERIFY: skip browser — tooling only (assumption A3).

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where to fix | A: `ParallelTestCommand` loops one ParaTest path per dir. B: `gate.js` loops / collapses to one path. C: multi-dir falls back to non-parallel `artisan test` (husky style) | **A** | Signature already claims `{dirs?*}`; gate argv is already correct; fixes every caller, not only gate. B leaves the command lying. C abandons parallelism for the common multi-domain gate case. |
| 2 | Multi-dir scheduling | Sequential parallel runs vs one merged PHPUnit config | **Sequential parallel runs** | Minimal; no temp config; reversible later if wall-clock hurts. |

## 8. File layout

```
app/Console/Commands/ParallelTestCommand.php   # behaviour change
tests/… or Feature test colocated as PLAN chooses   # regression
```

No new classes required unless PLAN prefers extracting a tiny argv helper —
keep it in the command if it stays short.

## 9. Risks acknowledged

- **Wall clock:** N sequential parallel boots cost more than one parallel full
  suite for large N. Revisit (merge config, or husky-style non-parallel) if
  scoped multi-domain gates become slower than `-- --all` in practice.
- **Argv / flag forwarding:** today’s raw `$_SERVER['argv']` rewrite is fragile;
  multi-dir must keep using the same injection pattern Collision expects, or
  tests will flake. Trigger to revisit: any new flag on `test:parallel` breaks.
