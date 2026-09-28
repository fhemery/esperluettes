# Admin pages — flash message shown twice — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Admin layout owns the flash block | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

One phase only: the fix is 23 one-line Blade deletions plus two tests, and the
tests only go green together with the deletions.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Admin layout owns the flash block

**Goal.** Every admin page renders a flash / validation-error message exactly
once, because only the admin layout renders `<x-shared::flash-block />`.

Reads: architecture §1, §4, §6. Nothing else needed.

**Context.** `<x-admin::layout>`
(`app/Domains/Administration/Private/Resources/views/layouts/layout.blade.php`,
line 12) already renders `<x-shared::flash-block />`. The 23 page views below
render it a second time. The component is `position: fixed` and takes no props,
so removing the page-level copy changes nothing visually except the duplicate.
All 23 files use `<x-admin::layout>` (verified at PLAN). Do **not** touch the
layout, the component, or `Shared/Resources/views/layouts/app.blade.php`.

**Deliverables.**

1. Tests (write first, see them fail):
   - `app/Domains/Config/Tests/Feature/Admin/FeatureToggleFlashTest.php` —
     Pest, `uses(TestCase::class, RefreshDatabase::class)`, same style as the
     sibling `FeatureToggleControllerTest.php` (actor via the `admin($this)`
     helper, route `config.admin.feature-toggles.index`).
   - `app/Domains/Administration/Tests/Feature/AdminPagesFlashBlockGuardTest.php`
     — Pest, no DB. Globs
     `base_path('app/Domains/*/Private/Resources/views/pages/admin')`
     recursively for `*.blade.php` (use `Symfony\Component\Finder\Finder` or
     `File::allFiles` per matching dir) and collects every file containing
     `x-shared::flash-block`. Asserts the list is empty; the failure message
     lists offending paths and says the admin layout owns the flash block.
     Also assert the scan found > 0 files, so a wrong path cannot pass vacuously.
     It must import no domain code (deptrac).
2. Delete the `<x-shared::flash-block />` line — plus the blank line it leaves
   doubled, or any wrapper left empty — from each of these 23 files
   (all under `app/Domains/<Domain>/Private/Resources/views/pages/admin/`):
   - Auth: `roles/index`, `roles/create`, `roles/edit`,
     `activation-codes/index`, `activation-codes/create`
   - Calendar: `activities/index`, `activities/create`, `activities/edit`
   - FAQ: `faq-categories/index`, `faq-categories/create`, `faq-categories/edit`,
     `faq-questions/index`, `faq-questions/create`, `faq-questions/edit`
   - Moderation: `moderation-reasons/index`, `moderation-reasons/create`,
     `moderation-reasons/edit`, `moderation-reports/index`,
     `moderation-reports/show`
   - Events: `domain-events/index`, `domain-events/show`
   - Config: `feature-toggles/index`, `feature-toggles/edit`

   Check after: `grep -rl "x-shared::flash-block" app/Domains` returns exactly
   the admin layout, `Shared/Resources/views/layouts/app.blade.php` and
   `Shared/Tests/Feature/View/Components/FlashBlockTest.php`.

**Tests.**
- `FeatureToggleFlashTest`:
  - `it('shows a success flash exactly once on the feature toggles index')` —
    `actingAs(admin($this))->withSession(['success' => 'flash-once-<unique>'])
    ->get(route('config.admin.feature-toggles.index'))`, assert OK and
    `substr_count($response->getContent(), 'flash-once-<unique>') === 1`.
    Fails before the fix (count 2).
  - `it('shows a validation error exactly once on the feature toggles index')` —
    same with an error bag in session (`withSession(['errors' => (new ViewErrorBag)->put('default', new MessageBag(['x' => 'err-once-<unique>']))])`);
    count === 1. If the index view also prints `$errors` inline for a field,
    pick a key the page does not render inline so the count stays meaningful.
- `AdminPagesFlashBlockGuardTest`:
  - `it('forbids x-shared::flash-block in admin page views, the admin layout owns it')`.
    Fails before the fix (lists 23 files).
- Existing `Shared/Tests/Feature/View/Components/FlashBlockTest.php` and all
  admin controller tests stay green unchanged.

**Acceptance.**
- ✅ Both new tests fail before the Blade deletions and pass after.
- ✅ An admin landing on `/admin/.../feature-toggles` with a `success` flash sees
  the message once in the HTML.
- ✅ No file under `app/Domains/*/Private/Resources/views/pages/admin/` contains
  `x-shared::flash-block`; the admin layout still does.
- ✅ No PHP file other than the two new tests is changed; the layout and the
  flash-block component are untouched.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Config › feature toggles, edit → save (admin/tech-admin) | Redirect to index shows the success toast **once**, auto-hides after ~10 s | |
| Config › feature toggles edit, invalid submit (if reachable) | Validation-error toast shows once | |
| Auth › roles, create a role | Success toast once on the roles index | |
| Auth › activation codes, create a code | Success toast once | |
| FAQ › categories or questions, create/edit | Success toast once; form validation error (empty required field) shows once | |
| Moderation › reasons create; reports show → change status (moderator) | Toast once, as moderator (different role than admin) | |
| Calendar › activities, create/edit | Toast once | |
| Events › domain events index | Page renders normally, no stray empty toast/gap where the block was removed | |
| Admin page that never had its own block (e.g. maintenance toggle or logs action) | Still shows its flash once — layout's block still works | |
| Any of the above, mobile width (~375 px) | Single toast, positioned as before, not overlapping twice | |
| Public page with a flash (e.g. profile save) | Unchanged: toast still shows once | |

## Open items

None. Verified at PLAN: the 23 page views listed exist, each contains the block
and uses `<x-admin::layout>`; the layout renders the block at line 12; admin
tests live under `app/Domains/<Domain>/Tests/Feature/` and phpunit picks up
`app/Domains/*/Tests/Feature`; `admin($this)` and route
`config.admin.feature-toggles.index` are used by the existing
`FeatureToggleControllerTest`.
