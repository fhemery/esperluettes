# Loop improvements — request

*Standing collector. WRAP's retro (`wrap-task` §0) appends numbered
suggestions here; run the task when the list is worth a pass. Once wrapped, the
next retro starts a fresh one.*

## What I want

Improvements to the loop protocol (`.agents/loop/`), the step skills, the
agent shims, the docs and the dev scripts, surfaced by finished tasks.

1. **The e2e teardown stops the dev app.** *(improve-loop)* `e2e/support/sail.ts`
   runs `pkill -f 'artisan serve'` in the container, which also kills Sail's own
   `artisan serve` on `:80`. Every `pnpm run e2e` leaves `http://localhost`
   dead until `./vendor/bin/sail restart laravel.test`. Narrowing the pattern
   to `artisan serve --env=e2e` was checked by hand to spare `:80`. Fix the
   script, then drop the restart advice from `verify-visually` and
   `e2e/README.md`.
2. **The gate needs Sail even for a docs-only change.** *(improve-loop)* REFINE
   commits planning docs only, yet `pnpm run gate` failed on deptrac because
   Sail was not running; the PHP suite is already skipped for non-code
   changes. Skip deptrac the same way when no PHP file changed, or say in the
   loop README that Sail must be up before any step's gate.
3. **No documented light path for tooling/docs tasks.** *(improve-loop)* The
   user judged DESIGN, PLAN and VERIFY useless for a docs-and-one-script task
   and had the orchestrator edit directly after REFINE (DECISIONS #7). The loop
   only knows `interactive` and `auto`, both six steps with subagent BUILD.
   Consider a documented rule — e.g. REFINE → direct edits → light WRAP when
   the spec is settled and no app code changes — so the next such task does
   not have to argue for it.
4. **Alpine `$el` / `$root` trap, rediscovered twice.** *(annotations)* At
   checkpoint 10v the capture form's « Enregistrer » stored nothing: methods
   read config from `this.$el.dataset`, and `$el` is the clicked button
   (fix `9dbeecf3`); in phase 11 `$root` was not the banner inside
   `<x-shared::modal>` / `<x-shared::button>`'s own `x-data` (A11). Vitest
   called methods directly, so it missed both. Fix: a front-end rule in
   `app/Domains/Shared/AGENTS.md` — read component config once in `init()`
   (closure or `x-ref`), never from `$el`/`$root` in methods — and a vitest
   pattern that clicks the button.
5. **SQLite tests do not catch MySQL's 64-char identifier limit.**
   *(annotations, A6)* The generated name of a composite index was 68 chars;
   found by reading, not by a test. Fix: a line in `AGENTS.md` § Migrations
   ("name composite indexes explicitly; MySQL caps identifiers at 64") or a
   gate check on migration index names.
6. **E2E seed gaps forced throwaway-DB edits.** *(annotations)* No seeded
   chapter has a `chapter-choice` block (injected by hand at 10v and again at
   VERIFY), no single text block was over 500 chars (seeder extended), and the
   only beta reader is also a moderator, so beta-reader visibility cannot be
   checked in a browser. Fix: seed a chapter-choice block and a pure beta
   reader in `E2eStorySeeder`.
7. **PLAN named a `tests/core/` path for a new spec.** *(annotations, A13)*
   The plan put the feature's e2e spec straight into `core/`, against
   `e2e/README.md`'s `features/` default, leaving BUILD to arbitrate. Fix:
   `plan-phases` skill says new specs go to `features/` unless the plan states
   the promotion reason.
8. **"Push back to BUILD" from VERIFY has no landing spot.**
   *(media-sound-upload)* VERIFY passed and listed three feature tests "to add
   in BUILD" (guest redirect, ARCHIVED recipient, reveal without a sound), but
   every phase was already `DONE`, so the backlog moved straight to `WIP:WRAP`
   and the tests were never written — WRAP found them by reading. Fix:
   `verify-visually` appends a fix phase row (`6 · TODO`) to `03-plan.md`'s
   index for any pushed-back item and leaves the backlog at `WIP:BUILD`;
   `continue-task` refuses to advance to WRAP while a phase row is `TODO`.
9. **Retiring a feature spec orphans its page-object helpers.** *(annotations,
   annotations-v2)* v1's WRAP deleted its spec and left ten callerless helpers
   that v2 had to prune (A15); v2's spec would orphan ~35 more. Fix: `wrap-task`
   §4 says "delete the spec **and** every page-object member / fixture left
   without a caller, in the same commit"; or `verify-visually` keeps
   feature-only helpers inside the spec file, so they die with it.
10. **PLAN silently weakened a spec requirement.** *(annotations-v2, A17)* Spec
   §4.1.3 said a cross-block selection *hides* the emoji; plan phase 8 made it a
   click no-op. VERIFY failed the row and a decision + fix commit followed
   (#13). Fix: `plan-phases` — any acceptance criterion that differs from the
   spec's wording goes to "Open items", never silently into a phase.
11. **File-local Pest helpers break under `test:parallel`.** *(annotations-v2,
    A8)* Two test files defining/using the same top-level helper failed only
    in parallel runs. Fix: one line in `AGENTS.md` § Testing — shared Pest
    helpers live in the domain's `Tests/helpers.php`, never in a test file.
12. **`.env.e2e` `APP_URL` points at the wrong port.** *(annotations-v2,
    VERIFY)* `APP_URL=http://localhost:8081` while the suite serves on `:8080`:
    avatars and chapter illustrations are broken images in every e2e
    screenshot. Fix the env file (or derive the port in `e2e/support/sail.ts`).
13. **Script stack order for Alpine registrations is undocumented.**
    *(annotations-v3, A15)* Architecture §9 flagged the risk, but PLAN still
    wrote `@push('scripts')` for a bundle registering `Alpine.data` on
    `alpine:init`. `scripts` is emitted after `app.js`'s `Alpine.start()`, so
    BUILD had to rediscover `head-scripts`. The same phase hit a Blade trap:
    `<x-comment::foo-bar>` looks for a class named `FooBar`, which needs an
    explicit `Blade::component` alias. Fix: one rule each in
    `app/Domains/Shared/AGENTS.md` (front-end): "bundles that register Alpine
    data/stores go to `head-scripts`" and "class components whose name differs
    from the tag need an explicit alias".
14. **Alpine `x-show` shows one frame late.** *(annotations-v3, A21)* The
    gutter layout ran in a `requestAnimationFrame` and measured an element
    still `display:none` and an article not yet pushed down by a freshly shown
    menu. Two VERIFY rows failed, which vitest cannot see. Fix: append to
    item 4's Shared front-end rule: "measure layout from a `ResizeObserver` on
    the toggled element, never in the same frame as an `x-show` change".
15. **The VERIFY summary goes stale after a VERIFY fix.** *(annotations-v3)*
    `03-plan.md` still says "54 passed, 5 failed" after `eac8fd90` fixed the
    three ❌ rows and the spec passes 36/36; WRAP had to reconcile them from
    the commit message. Fix: `verify-visually` rewrites its result paragraph
    (counts, HEAD) whenever a fix lands before the step closes.
16. **A third Alpine trap: state-touching `x-init` inside `x-html`.**
    *(fanart-artwork-module, VERIFY)* A lazy group fragment injected by an outer
    `x-html` called `load()` synchronously in `x-init`; the outer effect tracked
    the group's state and « Montrer tout » / « Cacher tout » did nothing. PHP
    tests and checkpoint 18v missed it; VERIFY's e2e found it (fix `b943dcce`,
    `$nextTick`). Fix: add to item 4's Shared front-end rule — "in a fragment
    injected by `x-html`, defer state-touching work in `x-init` with
    `$nextTick`".
17. **Fetched HTML fragments follow middleware redirects.**
    *(fanart-artwork-module, VERIFY)* Fragment routes behind `compliant` answer
    a 302 to the CGU page; `fetch` follows it and the whole page is injected
    into a modal. Nothing in DESIGN or PLAN prompted for it. Fix: a Shared
    front-end rule ("a `fetch` of an HTML fragment checks `response.redirected`
    and navigates instead of injecting"), and a line in `design-architecture`
    asking how lazy fragments handle auth / `compliant` redirects. The Fanart
    instance is backlog `fanart-fragment-redirects/`.
18. **The confirmed-only route middleware is not written down.**
    *(fanart-artwork-module, plan O3)* Architecture specified
    `web, auth, verified`; PLAN found by reading Quote that the convention is
    `['web','auth','compliant','role:user-confirmed,moderator,admin,tech-admin']`.
    Fix: one line in `AGENTS.md` § Controllers (or Security) stating it.
19. **A spec "kept" in `features/` rots unseen.** *(fanart-message-to-author,
    VERIFY)* `fanart-artwork-module`'s WRAP kept its spec in `features/` at the
    user's request — neither deleted nor promoted to `core/`. Nothing runs it,
    and its « reader proposes an image » case was already failing (seeder left
    the default `designated` mode, so readers got 403); found only because the
    next fanart VERIFY ran it. Fix: `wrap-task` §4 allows no third state — keep
    means promote to `core/` (with the reason), and WRAP runs the promoted spec
    once before committing.
20. **Alpine `x-data` arguments are frozen at init.** *(annotations-v4,
    phase 5)* In the in-chapter popover, `<template x-if="current.can_report">`
    around `x-data="reportButton('annotation', current.id)"` would keep the
    first annotation's id when paging between members: `current` stays truthy,
    so the component is never re-created. Fixed with `x-for` over
    `[current.id]` keyed on the id. Fix: add to item 4's Shared front-end rule —
    "a nested `x-data` whose arguments depend on a changing object must be
    re-keyed (`x-for … :key`), not wrapped in `x-if`".
21. **The outline error button is illegible.** *(annotations-v4, VERIFY
    finding 1)* PLAN prescribed `<x-shared::button color="error" :outline="true">`;
    `.surface-error.is-outline` (`Shared/Resources/css/app.css`) uses the pale
    `--color-error-bg` as text/border on white, so « Signaler » was near
    invisible on four sites, caught only by screenshots. Fix: correct the
    variant's foreground in Shared CSS, or a line in `app/Domains/Shared/AGENTS.md`
    saying which button colours support `:outline`.
22. **Deploy-time preconditions have no home.** *(quote-hard-delete, arch §9)*
    The purge migration is safe only if production has no deactivated user
    when it runs. That check lives only in the plan's "Open items" and the
    archived README, and nothing makes the person deploying see it. Fix: a
    "Deployment notes" section in the loop README template that WRAP fills in,
    plus a backlog-line convention (e.g. `⚠ deploy:`), until `release-notes/`
    provides a proper place for it.

## Why

So that what one task learns the hard way is fixed for the next, instead of
being rediscovered.

## Constraints or ideas I already have

Each item names the task it came from. Skip or merge items freely at REFINE.

## Explicitly out of scope

Findings about a feature itself — those go to the backlog as their own tasks.
