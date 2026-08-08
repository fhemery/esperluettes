# News — moderator access — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Moderator News management access | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (1/1)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Moderator News management access

**Goal.** Moderators join admins and tech-admins at every existing News
management gate — admin routes, form authorization, draft preview, admin nav,
and the show-page edit link.

**Architecture sections.** §3.3 (policy / authorization), §3.5 (routes and form
requests), §4 (frontend — existing Blade role checks only), §6 (testing
strategy).

**Deliverables.**
- `app/Domains/News/Private/routes.php` — add `Roles::MODERATOR` to the admin
  route-group middleware string (canonical order:
  `MODERATOR, ADMIN, TECH_ADMIN`, matching architecture §3.3).
- `app/Domains/News/Private/Requests/NewsRequest.php` — include
  `Roles::MODERATOR` in `authorize()`.
- `app/Domains/News/Private/Controllers/NewsController.php` — include
  `Roles::MODERATOR` in both `AuthPublicApi::hasAnyRole()` checks on the
  `show` action (draft lookup and draft status guard).
- `app/Domains/News/Public/Providers/NewsServiceProvider.php` — include
  `Roles::MODERATOR` in the role arrays passed to both
  `AdminNavigationRegistry::registerPage()` calls (news management and pinned
  carousel).
- `app/Domains/News/Private/Resources/views/pages/show.blade.php` — add
  `'moderator'` to the `hasRole()` list on the edit-link guard.
- `app/Domains/News/AGENTS.md` — update the draft-preview and admin-nav bullets
  to name `MODERATOR` alongside `ADMIN` and `TECH_ADMIN`.
- `app/Domains/News/README.md` — replace "administrators" wording for article
  management with the three manager roles; note moderators in the draft-preview
  sentence.
- `app/Domains/News/Tests/Feature/Admin/NewsControllerTest.php` — add moderator
  cases (see Tests).
- `app/Domains/News/Tests/Feature/Admin/PinnedNewsControllerTest.php` — add
  moderator cases (see Tests).
- `app/Domains/News/Tests/Feature/NewsDetailsTest.php` — add moderator draft
  preview and edit-link cases (see Tests).

No new classes, migrations, or deptrac changes.

**Tests.**
Use the existing `moderator($this)` helper from
`app/Domains/Auth/Tests/helpers.php`. Extend existing suites; do not add a new
test file.

- `News Admin Controller › index › allows access for moderator users` —
  `actingAs(moderator($this))->get(route('news.admin.index'))` → `assertOk()`.
- `News Admin Controller › store › creates a draft news item for moderators` —
  mirror the admin store test with `moderator($this)` → redirect + DB assert.
- `Pinned News Admin Controller › index › allows access for moderator users` —
  `get(route('news.admin.pinned.index'))` → `assertOk()`.
- `Pinned News Admin Controller › reorder › reorders pinned news for moderators`
  — mirror the admin reorder PUT test with `moderator($this)`.
- `News Details Test › shows draft preview with banner to moderators` — mirror
  the existing admin/tech-admin draft-preview test with `moderator($this)`.
- `News Details Test › shows edit link to moderators on published news` —
  published article, `actingAs(moderator($this))`, assert response contains
  `route('news.admin.edit', $news)`.

Existing denial tests (`denies access to non-admin users`, guest 404 on draft,
confirmed user 404 on draft) must remain green unchanged.

**Acceptance.**
- ✅ A moderator can open `news.admin.index`, create news via
  `news.admin.store`, open `news.admin.pinned.index`, and reorder pinned items
  via `news.admin.pinned.reorder`.
- ✅ A moderator visiting `/news/{slug}` for a draft sees the article and draft
  banner (not 404).
- ✅ A moderator on a published show page sees the edit link to
  `news.admin.edit`.
- ✅ A confirmed non-moderator user is still redirected from admin routes and
  gets 404 on draft show (existing tests pass).
- ✅ A guest still gets 404 on draft show and sees no edit link on published
  news (existing tests pass).
- ✅ Admin and tech-admin behaviour is unchanged (existing tests pass).
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Admin sidebar (moderator) | "Actualités" and pinned/carousel nav entries visible; both pages load | |
| Admin news list (moderator) | Draft and published rows visible; create link works | |
| Admin create/edit (moderator) | Form loads; save draft and publish both succeed | |
| Admin pinned/carousel (moderator) | Pinned list loads; drag/reorder persists | |
| Public show — draft (moderator) | Draft preview banner visible; article body renders | |
| Public show — published (moderator) | Edit icon/button links to admin edit form | |
| Public show — draft (guest) | 404 | |
| Public show — draft (confirmed user) | 404 | |
| Public show — published (guest) | Article readable; no edit affordance | |
| Admin sidebar (admin) | Unchanged — News entries still present and working | |
| Mobile admin (moderator) | News list and create form usable on narrow viewport | |

## Open items

| Item | Phase | Notes |
|------|-------|-------|
| `moderator($this)` helper availability | 1 | **Verified** — loaded via `tests/Pest.php` → `Auth/Tests/helpers.php`. |
| Admin nav has no feature test | 1 | Nav registration is exercised only in VERIFY smoke; middleware + route tests prove access if the URL is known. Accept per architecture §6. |
| Edit-link test baseline | 1 | No existing test asserts the admin edit link on show; add moderator case only — admin/tech-admin behaviour covered implicitly by unchanged Blade list. |
