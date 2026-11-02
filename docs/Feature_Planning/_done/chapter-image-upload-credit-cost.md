# Credit cost for uploading an image into a chapter

**Status:** DONE — 2026-11-02 · **Domain(s):** `Story` (Editor: one optional prop)

## What it does

Each freshly uploaded image file in a chapter costs the author 1 chapter credit
(same currency as chapter creation), on create and on edit. A save the balance
cannot cover is rejected whole: no file stored, nothing spent, input kept. The
chapter form warns beforehand with a per-field notice and a live
"N images à importer · M crédits disponibles" counter (informational, never blocks submit).

## Key behaviour

- Cost on create = `1 + N` new uploads; on edit = `N` (editing itself is free).
- Free: library reuse, images already saved in the chapter, unchanged re-saves. No refund on replace/remove/delete.
- No staff exemption, cost fixed at 1 (not configurable), no notification, no audit event (credit movements emit none).
- Shortfall caused by images is a `credits` `ValidationException` (French message with cost and balance, `:balance` clamped to 0). A 0 balance on create stays a 403 `AuthorizationException` (unreachable from the UI).
- Order matters: pre-check BEFORE `ChapterContentResolver::resolve()` stores any file; spend FIRST in the save transaction (before writes/events). Reordering either keeps files of rejected saves or leaves listener side effects after rollback.
- Lost concurrent race: stored files become orphans for `media:gc`; nothing spent, no chapter written.
- News / StaticPage uses of `<x-editor::multi>` stay free and show no notice. Author-note rich text out of scope.

## Where the code lives

| Concern | Path |
|---------|------|
| Guarded spend | `Story/Private/Services/ChapterCreditService::trySpend($userId, $n)` (single conditional `UPDATE`; also used for the creation credit, replacing the trailing `spendOne()`) |
| Count + check + spend | `Story/Private/Services/ChapterService.php` (create + update), `Story/Private/Support/ChapterContentResolver::countNewUploads()` |
| Controller | `Story/Private/Controllers/ChapterController.php` (passes clamped `availableCredits`) |
| Views | `Story/Private/Resources/views/chapters/{create,edit}.blade.php`, `partials/form.blade.php` |
| Editor prop | `Editor/.../components/multi.blade.php` + `multi/_image-block.blade.php` (`imageNotice` -> media field `helpText`) |
| JS | `Story/Private/Resources/js/chapter-image-credits.js` (Vite entry in `vite.config.js`, loaded via `@push('head-scripts')`) |
| Lang | `Story/Private/Resources/lang/fr/{chapters,validation}.php` |
| Tests | `Story/Tests/Feature/Chapters/ChapterImageCreditCostTest.php`, `Story/Tests/Feature/ChapterCreditTrySpendTest.php`, `Editor/Tests/Feature/MultiEditorComponentTest.php`, `chapter-image-credits.test.js` (vitest), `Story/Tests/helpers.php::setAvailableCredits` |
| e2e | `e2e/tests/features/chapter-image-credits.spec.ts` (3 tests), `e2e/support/credits.ts::setAuthorCredits`, `ChapterCreatePage` / `ChapterEditPage` additions |

## Extension points used

- Editor `<x-editor::multi>` gained optional `imageNotice` (default `null` = nothing); no new events from Media or Editor.
- No new registry, migration, route, notification or deptrac edge.

## Decisions worth remembering

- One guarded `trySpend($n)` `UPDATE` instead of a `trySpendOne` loop (atomic, no partial spend).
- Counter lives in a Story Vite module that counts `blocks[<uid>][file]` inputs from outside and uses a `MutationObserver` + `click` (remove/choose-existing clear the input without a `change` event). Media state is not touched.
- The « solde insuffisant » hint in the counter is a single label, cheapest to drop.
- Story tests run with `APP_LOCALE=zz`: assert raw keys (`story::validation.chapter.credits.insufficient`, `story::chapters.form.content.image_credit_notice`).

## Counter-intuitive bits

- The e2e author starts with 5 credits (`E2eStorySeeder`); the spec spends 3, so later feature specs must not assume 5 (specs 2-3 set the balance via `setAuthorCredits`).
- A rejected save shows the message twice (global toast + inline slot), consistent with the rest of the app.
- A freshly picked image block requires the property-confirm checkbox.
- `Storage::fake('public')` is not empty after registration; scope disk assertions to `allFiles('chapters')`.

## Not done

- Non-goals: refunds, staff exemption, configurable cost, notifications, audit event, author-note editor.
- Divergence from plan: the planned `trySpendOne` loop (A2) was replaced by `trySpend($n)`; the `Story/AGENTS.md` "check before, spend after" invariant was rewritten accordingly.
- e2e: `features/chapter-image-credits.spec.ts` and `support/credits.ts` deliberately KEPT (not deleted per the usual WRAP default, not promoted to `core/`): they are the only browser coverage of the edit-form counter and the insufficient-credit state. Delete them when a core spec covers that.
- Pre-existing, unrelated: forced dark appearance dims "Publié" / "Annuler" on the chapter form.
- No new backlog rows.
