# Credit cost for uploading an image into a chapter — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).
>
> A section still reading `_Not written yet._` has not been done — that is how
> `/continue-task` knows where the task stands.

**Complexity:** 3 /5 · **Mode:** interactive

## 0. Handoff

### REFINE → DESIGN+PLAN (2026-11-02)

- 4 blocking questions were asked (by the orchestrator, before this run); all answered, see §4 decisions 1-4.
- Code facts re-verified: Story owns credits (`ChapterCreditService::trySpendOne`, exposed as `StoryPublicApi::spendChapterCredit`); `ChapterService` creation checks `availableForUser() > 0` then `spendOne` (lines ~51, ~105); `ChapterContentResolver::resolve()` is the only place calling `media->store()` for chapter images; there is no AJAX upload (file travels with the form post).
- Parked for DESIGN: `<x-editor::multi>` is shared with News and StaticPage (must stay free) — at most an optional notice prop passed by the Story chapter form. The live "pending uploads vs balance" count is client-side only; the server remains authoritative. All-or-nothing means the count and the balance check must happen before any `media->store()`, and the spend must sit in the save transaction.
- Likeliest to be reversed: no refund on replace/remove/delete (assumption A3), and the cost applying on chapter edit as well as creation.

### DESIGN+PLAN → BUILD (2026-11-02)

- §1 reviewed against the code: no gaps found. There are 3 phases and no checkpoint, and nothing was split. No question was asked: every tradeoff had a clearly better option (§2.7).
- **Riskiest phase: 1.** Today `resolve()` (which stores media) runs *before* the credit check in `createChapter`, and `spendOne()` runs *last* in the transaction, after `ChapterCreated` is emitted. Phase 1 moves the count and pre-check before `resolve()`, and moves the spend to the top of the transaction. Getting that order wrong either stores files for a rejected save or leaves event side effects behind after a rollback.
- **Verified in the code:**
  - `ChapterCreditService` emits no event for any credit movement (`EventBus` is injected but unused), so the §1.8 open item resolves to "no audit event".
  - `<x-media::image-field>` already has `helpText`.
  - The `{name}[file]` input is cleared by `remove()` and `chooseExisting()` without firing `change`, which is why the counter also listens to `click` and uses a `MutationObserver`.
  - Existing chapter tests upload at most one file, against the 5 starter credits.
- **Likeliest to be reversed:** A8 (edits cost credits too) and A3 (no refund), both from REFINE. From this step, A9 (the « solde insuffisant » hint in the counter) is the cheapest to drop.

### BUILD phase 1 → next (2026-11-02)

- No deviation from the plan. Story tests run with `APP_LOCALE=zz`, so they assert the raw key `story::validation.chapter.credits.insufficient`, not the French text; phase 2's notice test must likewise assert the key (`story::chapters.form.content.image_credit_notice`), not the rendered sentence.
- `Storage::fake('public')` is not empty after registration (avatar files); scope disk assertions to `allFiles('chapters')`.
- New helper `setAvailableCredits($userId, $n)` in `Story/Tests/helpers.php` (sets gained = n, spent = 0); `setUserCredits` only sets `credits_gained`.

### BUILD phase 2 → next (2026-11-02)

- No deviation. `availableCredits` is clamped with `max(0, …)` in the controller (legacy rows can be negative, as in A15). The notice sentence ends with a full stop; tests assert the raw key (`APP_LOCALE=zz`).
- `image-field` renders the help text as `<p class="text-xs text-fg/60">`; the "absent by default" Blade test relies on that exact markup. Phase 3 adds the counter near the same form section, and the form now requires `$availableCredits` (both views supply it).

## 1. Functional spec

### 1.1 Overview

Uploading a new image file into a chapter's content now costs the author one chapter credit per file, the same currency already spent to create a chapter or deposit an artwork. The chapter form warns the author beforehand, and a save that the balance cannot cover is rejected as a whole.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Crédit de chapitre | Existing currency, earned by commenting other chapters, spent to create a chapter |
| Image importée | A new file picked from the device in a chapter image field and saved with the chapter |
| Image réutilisée | An image picked from the media library, or already saved in the chapter |

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | N/A | N/A |
| `user` (non-confirmed) | N/A — cannot author chapters | N/A |
| `user-confirmed` author / co-author with a story | Notice and pending-count in the chapter form; own balance | Import images, paying 1 credit each |
| Moderator / Admin | Same as author when authoring | Same; no exemption (assumption A6) |

### 1.4 Functional requirements

**Charging rule.** Each newly uploaded file in a chapter save costs 1 credit. Free: library reuse, images already saved in the chapter, unchanged re-saves. No refund for images later replaced or removed.

**Create chapter.**
1. Author fills the form, picking N new files (N >= 0).
2. On submit, the required balance is 1 (creation) + N.
3. If balance is insufficient: nothing is saved, no file is stored, no credit is spent; the form returns with a French validation error stating the cost and the balance, and the author's input is kept.
4. Otherwise the chapter is created, files stored, 1 + N credits spent.

**Edit chapter.**
1. Author picks N new files; required balance is N (editing itself stays free).
2. Insufficient: same all-or-nothing rejection. Otherwise saved and N credits spent.
3. N = 0 changes nothing from today.

**Warning in the form.**
1. Beside each image field a notice reads « Importer une image coûte 1 crédit de chapitre, il vous en reste N » (N = current balance).
2. A live counter shows the images pending upload in the form versus the balance (e.g. « 2 images à importer · 3 crédits disponibles »), updating as files are picked or cleared; it also accounts for the chapter-creation credit on the create form.
3. Informational only: no modal, no confirmation, and the submit button is not blocked client-side (the server decides).

**Where it applies.** Chapter image fields of the Story chapter form. News and StaticPage uses of the shared editor stay free and show no notice. The author-note rich-text editor is out of scope.

### 1.5 Lifecycle

- Credits spent are final: deleting the chapter, removing or replacing the image, or deactivating the user refunds nothing.
- A failed save (validation or storage error) spends nothing.
- Deactivated/deleted user: cannot save, so nothing happens; no new data is stored by this feature.
- Parent story/chapter disappearing: no new data owned by this feature.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Confirmed authors only reach the form (existing rules); no exemption for staff (A6) |
| Visibility / privacy | Balance is already shown to the author; nothing new exposed |
| Settings | N/A — fixed cost of 1 credit, not configurable (A7) |
| Notifications | None (A4) |
| Domain events | None new; spend goes through the existing credit mechanism (DESIGN to check whether it emits one) |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | No refund; see §1.5 |
| Media | Charge happens only when a fresh file is stored; reuse/saved paths are free |
| Search | N/A |
| i18n | French messages for notice, counter and validation error |
| Mobile | Notice and counter must fit small screens |
| Accessibility | Counter is a live region; notice is text, not colour-only |

### 1.7 Out of scope

- Author-note rich-text editor and any editor image outside the chapter image fields.
- Refunds (replace, remove, chapter deletion).
- Charging in News, StaticPage or other consumers of the shared components.
- A modal/confirmation step; client-side blocking of submit.
- Configurable cost; notifications; changes to how credits are earned.
- Fanart deposit cost (unchanged).

### 1.8 Open questions

None blocking. Non-blocking: whether the spend should emit an audit event if the existing credit service does (DESIGN). Resolved at DESIGN: the existing service emits none, so no event (A10).

## 2. Architecture

### 2.1 Domain placement

All charging logic stays in **Story**, which already owns the credits table, the chapter form and the only code path that stores chapter images.

- **Story** — owns the rule: count the fresh uploads, check the balance, spend inside the save transaction, render the notice and the live counter.
- **Editor** — one additive optional prop on `<x-editor::multi>` (`imageNotice`, default `null`), forwarded to each image block. No behaviour change for News / StaticPage, which never pass it. Precedent: `needsPropertyConfirm`, a chapter-only prop forwarded the same way.
- **Media** — untouched. `<x-media::image-field>` already has a `helpText` prop; the notice travels through it.
- **Fanart** — untouched; it keeps `StoryPublicApi::spendChapterCredit()`.

### 2.2 Data model

No schema change. `story_chapter_credits` (`credits_gained`, `credits_spent`) is reused. Spending N credits means adding N to `credits_spent` in one atomic, guarded `UPDATE`.

Lifecycle (§1.5): nothing new is stored, so there is nothing to cascade. A save that fails before the transaction commits spends nothing, because the spend runs in the same transaction as the chapter write.

### 2.3 PHP architecture

**Credit service (Story, private).** It gets one new primitive:

```php
// ChapterCreditService
public function trySpend(int $userId, int $amount): bool
```

- It runs one `UPDATE … SET credits_spent = credits_spent + :amount WHERE user_id = ? AND credits_gained - credits_spent >= :amount`, and returns `true` if it changed exactly one row.
- `$amount <= 0` returns `true` and runs no query.
- It never creates a row and never drives the balance below zero.
- It replaces the "loop over `trySpendOne`" idea from A2: one statement is atomic on its own, and a partial loop would need its own rollback.
- `trySpendOne` and `spendOne` stay as they are (Fanart and listeners use them).

**Counting fresh uploads.** `ChapterContentResolver` gets:

```php
public function countNewUploads(array $data): int
```

- It returns 0 unless `mode === 'advanced'`.
- Otherwise it counts the blocks listed in `blocks_order` whose `type === 'image'` and whose `file` is an `UploadedFile`. These are exactly the blocks for which `resolve()` calls `media->store()`.
- `resolve()` and `countNewUploads()` share one private iterator over the ordered blocks, so the two cannot disagree about what counts as a fresh upload.
- Reused paths (library or already saved) are free by construction, because they arrive as `path`, not `file`.

**Enforcement point: `ChapterService`.** This is the only caller of `resolve()`.

| Step | `createChapter` | `updateChapter` |
|------|-----------------|-----------------|
| a. existing guard | `available <= 0` throws `AuthorizationException`, unchanged. The form is unreachable with 0 credits anyway (`canCreateChapter`) | none |
| b. count | `N = countNewUploads(data)` | same |
| c. pre-check, **before `resolve()`** | `available < 1 + N` throws `ValidationException(['credits' => …])` | `N > 0 && available < N` throws the same |
| d. `resolve()` | stores media, as today | same |
| e. in the transaction, **first statement** | `trySpend(userId, 1 + N)`. On `false`, throw the same `ValidationException`, which rolls back | `trySpend(userId, N)`, same |

Notes on this flow:

- **The spend moves to the top of the transaction.** In creation it replaces today's trailing `spendOne()`. It must run before `ChapterCreated` is emitted and before co-authors are notified, so that a failed spend never leaves listener side effects behind.
- **Who pays.** The acting user (`$userId`), the same as the upload scope `chapters/{actingUserId}`. A co-author pays for their own uploads.
- **Why the pre-check exists.** It is what guarantees "no file stored" in the normal case.
- **The race window.** The in-transaction `trySpend` covers a concurrent spend between steps c and e. In that case the stored files become orphans, and the existing `media:gc` sweep collects them. This is accepted (§2.8).
- **The error message** is a French string carrying the cost and the balance. Proposed key: `story::validation.chapter.credits.insufficient`, with `:cost` and `:balance` (« Cet enregistrement coûte :cost crédit(s) de chapitre, il vous en reste :balance. »). Both controllers already turn a `ValidationException` into `back()->withErrors()->withInput()`, so the controller needs no change for the error.
- **Audit event (open item §1.8): none.** `ChapterCreditService` injects `EventBus` but emits nothing for any grant or spend, and Fanart's artwork spend emits nothing either. Adding one event only for this spend would be speculative. `ChapterCreated` / `ChapterUpdated` are still emitted as before.

**Controllers.** `ChapterController::create` and `::edit` additionally pass `availableCredits` (`ChapterCreditService::availableForUser`, already injected) to the views. They add no route and no form-request change. `blocks.*.file` is already validated as `image|max:2048`.

### 2.4 Frontend

**Notice, per image field (server-rendered).**

- The Story form partial passes `imageNotice` to `<x-editor::multi>`, with the text « Importer une image coûte 1 crédit de chapitre, il vous en reste :balance ».
- `multi` forwards it to `_image-block`, both for the stored blocks and for the `<template data-block-template="image">`, so blocks added later get it too.
- `_image-block` passes it to `<x-media::image-field>` as `helpText`, which renders it as plain text.
- With the default `null`, nothing is rendered, so News and StaticPage are unchanged.

**Live counter, once per form (Story-owned Alpine).**

- A new Story JS module registers `Alpine.data('chapterImageCredits', { balance, creationCost, labels })`. `creationCost` is 1 on the create form and 0 on edit.
- It wraps the content section of the form partial.
- It counts `input[type=file][name^="blocks["][name$="[file]"]` with `files.length > 0` under its root. The count is 0 when the form's `mode` input is not `advanced`.
- The `{name}[file]` input name is the image-field's documented contract, so this reads public DOM, not Media's internal Alpine state.
- **When it recounts:**
  - on delegated `change` (a native file pick), `drop` and `click` events, on the next tick, because `remove()` and `chooseExisting()` clear the input from a button click without firing `change`;
  - from a `MutationObserver` on the root's subtree, which catches blocks being added or removed.
- **What it displays.** Create: « Coût de l'enregistrement : :total crédit(s) (1 pour le chapitre + :count image(s) à importer) · :balance disponible(s) ». Edit: « :count image(s) à importer · :balance crédit(s) disponible(s) ». When `total > balance`, it appends a textual « — solde insuffisant » (A9).
- **Plurals.** The labels are passed as one/other pairs and the JS picks between them.
- **Accessibility.** The element is `aria-live="polite"`.
- **No blocking.** It never disables submit.
- **Where the server error appears.** `<x-input-error :messages="$errors->get('credits')">` sits beside the counter.
- **Loading.** The module is a Vite entry, `app/Domains/Story/Private/Resources/js/chapter-image-credits.js`, loaded with `@vite` on the create and edit pages only, following the `fanart-viewer.js` / `charts.js` precedent.

### 2.5 Deptrac

No new edge. Story → Editor (the component) and Story → Media already exist. Editor → Media already exists for `image-field`. Everything else is internal to Story.

### 2.6 Testing strategy

- **Integration (Pest, Story):** create and edit with N fresh files and with enough, too few, or exactly enough credits, plus library reuse and saved paths (free). Each test asserts:
  - the balance after the save,
  - that no chapter row exists,
  - that no file is on the fake disk when the save is rejected,
  - the error key and the input kept.
  
  The race path is covered by forcing `trySpend` to fail, through a bound fake or by draining credits between the pre-check and the spend.
- **Unit:** `ChapterCreditService::trySpend` (exact balance, over balance, amount 0, no row).
- **Blade component (Editor):** `imageNotice` reaches stored image blocks and the image template, and is absent by default (the regression guard for News/StaticPage).
- **Vitest (Story JS):** the counting and label logic on a happy-dom fixture: pick, clear, library choice, block removal, simple mode, the create/edit cost, and the insufficient hint.
- **E2e (`e2e/tests/features/`):** a real browser picks two files and sees the counter update, then removes one. Only a browser proves that the Alpine wiring and the `MutationObserver` work together.
- **VERIFY:** layout of the notice and counter on mobile, and the server error rendering after a rejected save.

### 2.7 Tradeoffs locked

| # | Question | Options considered | Chosen | Why / what would flip it |
|---|----------|--------------------|--------|--------------------------|
| 1 | How to spend N credits atomically | (a) loop `trySpendOne` N times in the transaction; (b) a new guarded `trySpend($n)`; (c) check then `spendOne` N times | (b) | One statement, no partial state, no oversell. (c) is today's racy creation pattern. Flip only if MySQL rejects the parameterised guard (it does not) |
| 2 | Where the charge is enforced | (a) `ChapterContentResolver`; (b) `ChapterService`; (c) `ChapterRequest` | (b), counting delegated to the resolver | The service already owns credits and the transaction. The resolver stays a pure content transformer, and a form request cannot spend inside the save transaction |
| 3 | How the live counter learns about picks | (a) new events dispatched by Media/Editor; (b) read Media's internal `isNewFile` via `Alpine.$data`; (c) count the file inputs from the outside (delegated events plus `MutationObserver`) | (c) | Keeps the shared components to one optional prop (an orchestrator constraint) and depends only on the documented input name. Flip to (a) if a second consumer ever needs the same count |
| 4 | Audit event for the spend | emit a new `ChapterCreditsSpent` event / none | none | No credit movement emits an event today, Fanart included. Flip if credit history becomes a requirement, and then do it for all movements in one go |
| 5 | Error shape when the balance is insufficient | 403 `AuthorizationException` (today's creation guard) / `ValidationException` | `ValidationException` on `credits`; the 403 stays only for the 0-balance create guard | The spec requires the input to be kept with a French message. The 0-balance case is unreachable from the UI and left as is |
| 6 | Counter JS: inline `@push` script vs a Vite module | inline / module | module | Can be tested in vitest inside the gate; the inline Blade scripts (e.g. `mediaImageField`) cannot |

### 2.8 Risks acknowledged

- **Orphan files on a lost race.** Two concurrent saves can both pass the pre-check, and the loser's files stay stored until `media:gc` sweeps them. Revisit if orphans show up in the GC logs in volume.
- **Counter drift.** The counter recounts on events, so an image-field state change made with no click, change or drop (none exists today) would leave it stale. Only display depends on it; the server stays authoritative.
- **Over-sized pick.** `checkSize` clears the input when a file is too large, so the count is correct, but the preview of a previously accepted file can stay. This is pre-existing Media behaviour and is not touched here.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Server-side charge: count, pre-check, atomic spend — **risky** | M | — | DONE |
| 2 | Per-field notice: Editor `imageNotice` prop + Story form wiring | S | 1 | DONE |
| 3 | Live counter: Story Alpine module, vitest, e2e | M | 2 | TODO |

There is no checkpoint row. Phase 2 adds an optional prop that defaults to `null`, and its Blade test asserts that News-style usage renders unchanged. Phase 3 runs the `multi-editor` core e2e spec against the reshaped form.

Working agreement: one phase = one commit; failing test first; no phase N+1 before N's acceptance holds; re-ordering is surfaced, not taken silently.

### Phase 1 — Server-side charge (risky)

**Goal.** A chapter save that uploads N fresh image files spends N credits (plus 1 on creation), atomically. It is rejected as a whole with a French `credits` validation error when the balance falls short. **Reads.** §2.2, §2.3.

**Deliverables.**
- `app/Domains/Story/Private/Services/ChapterCreditService.php`: add `trySpend(int $userId, int $amount): bool` (guarded single `UPDATE`; `amount <= 0` returns `true` with no query).
- `app/Domains/Story/Private/Support/ChapterContentResolver.php`: add `countNewUploads(array $data): int`. Extract the ordered-block iteration so that `resolve()` and the counter share it, with no behaviour change to `resolve()`.
- `app/Domains/Story/Private/Services/ChapterService.php`:
  - `createChapter`: keep the `available <= 0` 403 guard, then count, then pre-check `available < 1 + N`, all before `resolve()`. Inside the transaction, call `trySpend($userId, 1 + N)` as the first statement, replacing the trailing `spendOne()`.
  - `updateChapter`: count, then pre-check `N > 0 && available < N` before `resolve()`, then `trySpend($userId, N)` first in the transaction.
  - On any shortfall, throw `ValidationException::withMessages(['credits' => __('story::validation.chapter.credits.insufficient', ['cost' => …, 'balance' => …])])`.
- `app/Domains/Story/Private/Resources/lang/fr/validation.php`: key `chapter.credits.insufficient`.
- `app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php`: `<x-input-error :messages="$errors->get('credits')" class="mt-2" />` under the content block errors, so the rejection is visible before phase 3 adds the counter.

**Tests.**
- New `app/Domains/Story/Tests/Feature/Chapters/ChapterImageCreditCostTest.php`, real confirmed author (`alice`), credits set with `setUserCredits`, `Storage::fake`:
  - `it('spends one credit per uploaded image on top of the creation credit')`: 3 credits, 2 files → chapter created, balance 0.
  - `it('rejects a creation the balance cannot cover and stores nothing')`: 2 credits, 2 files → no chapter, no file on the disk, balance 2, `credits` error, old input kept.
  - `it('charges nothing for a library or already saved image')`: a `path`-only image block on create costs 1; on edit it costs 0.
  - `it('spends one credit per uploaded image when editing')`: 2 credits, 2 files on edit → saved, balance 0.
  - `it('rejects an edit the balance cannot cover and leaves the chapter untouched')`: content unchanged, no file, balance unchanged.
  - `it('keeps editing free when no image is uploaded')`: balance 0, a text-only edit succeeds.
  - `it('ignores uploaded files in simple mode')`: no charge beyond creation.
  - `it('ignores a file attached to a block missing from blocks_order')`.
  - `it('charges the acting co-author, not the story owner')`.
  - `it('rolls back the chapter when the spend fails inside the transaction')`: bind a `ChapterCreditService` partial or subclass whose `trySpend` returns `false` → no chapter row, no `ChapterCreated` event, `credits` error.
- New `app/Domains/Story/Tests/Unit/ChapterCreditServiceTrySpendTest.php` (or a Feature test if DB is needed, following the domain's convention): spends the exact balance; refuses over the balance and leaves the row unchanged; amount 0 is a no-op returning `true`; no row returns `false` and creates nothing.
- The existing `ChapterAdvancedModeTest`, `CreateChapterTest` and `EditChapterTest` stay green. They upload at most one file against the 5 starter credits.

**Acceptance.**
- ✅ Creating with 2 files and 3 credits leaves 0. With 2 credits it is rejected with no chapter, no stored file, and 2 credits left.
- ✅ Editing with N files spends exactly N. Editing with no new file spends nothing, even at balance 0.
- ✅ Reused or saved image paths never cost anything.
- ✅ A failed in-transaction spend leaves no chapter row and emits no `ChapterCreated`.
- ✅ No new deptrac edge (`./vendor/bin/sail composer deptrac`).
- ✅ `pnpm run gate` green.

### Phase 2 — Per-field notice

**Goal.** Every chapter image field shows « Importer une image coûte 1 crédit de chapitre, il vous en reste N ». News and StaticPage image fields are unchanged. **Reads.** §2.1, §2.4 (first part). Phase 1 left the server charge in place and a `credits` error slot in the form partial.

**Deliverables.**
- `app/Domains/Editor/Private/Resources/views/components/multi.blade.php`: new prop `'imageNotice' => null`, documented in the header comment, forwarded in both `_image-block` includes (stored blocks and the template).
- `app/Domains/Editor/Private/Resources/views/components/multi/_image-block.blade.php`: pass `:help-text="$imageNotice ?? null"` to `<x-media::image-field>`, and list `$imageNotice` in the Vars comment.
- `app/Domains/Story/Private/Controllers/ChapterController.php`: `create()` and `edit()` add `'availableCredits' => $this->chapterCreditService->availableForUser((int) $request->user()->id)` to the view data.
- `app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php`: pass `:imageNotice="__('story::chapters.form.content.image_credit_notice', ['balance' => $availableCredits])"`.
- `app/Domains/Story/Private/Resources/lang/fr/chapters.php`: `form.content.image_credit_notice`.

**Tests.**
- `app/Domains/Editor/Tests/...` (the existing multi component test file): `it('forwards imageNotice to image blocks and the image template')` and `it('renders no image notice by default')`.
- `ChapterImageCreditCostTest`: `it('shows the image credit notice with the current balance on the create and edit forms')`.

**Acceptance.**
- ✅ The create and edit pages contain the notice with the author's live balance, including in the image block template.
- ✅ `<x-editor::multi>` without `imageNotice` renders no notice (the News/StaticPage regression guard).
- ✅ `pnpm run gate` green.

### Phase 3 — Live counter

**Goal.** The chapter form shows a live, accessible count of pending image uploads against the balance, including the creation credit on the create form. It never blocks submit. **Reads.** §2.4 (second part), §2.6. Phases 1 and 2 left the server charge, the `credits` error slot and the `availableCredits` view variable in place.

**Deliverables.**
- `app/Domains/Story/Private/Resources/js/chapter-image-credits.js`:
  - Exports a pure `countPendingUploads(root)` and a `buildLabel({count, balance, creationCost}, labels)` for vitest.
  - Registers `Alpine.data('chapterImageCredits', …)` on `alpine:init`.
  - Recounts on delegated `change`, `drop` and `click` (next tick) and from a `MutationObserver`, as described in §2.4.
- `vite.config.js`: add the entry. It is the only change outside `app/Domains`, following the precedent of the existing per-domain entries.
- `app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php`:
  - wrap the content section in `x-data="chapterImageCredits({...})"`, with `creationCost` 1 when `$chapter` is unset and 0 otherwise;
  - add the counter `<p aria-live="polite" data-testid="chapter-image-credits">` beside the `credits` error.
- `app/Domains/Story/Private/Resources/views/chapters/create.blade.php` and `edit.blade.php`: `@vite('app/Domains/Story/Private/Resources/js/chapter-image-credits.js')`.
- `app/Domains/Story/Private/Resources/lang/fr/chapters.php`: the counter labels (one/other pairs for create and edit, and the insufficient hint).
- `e2e/tests/features/chapter-image-credits.spec.ts`.

**Tests.**
- `app/Domains/Story/Private/Resources/js/chapter-image-credits.test.js`:
  - counts only file inputs that have files;
  - returns 0 when `mode` is simple;
  - the count drops after the input is cleared (simulating `remove` / library choice);
  - the count drops after the block node is removed;
  - the create label includes the creation credit;
  - the insufficient hint appears when the total exceeds the balance.
- E2e `chapter-image-credits.spec.ts`: the confirmed author opens the create form, switches to advanced, adds two image blocks, picks a file in each (the counter reads 2 images, total 3), removes one block (the counter updates), and submits successfully.
- The existing `e2e/tests/core/multi-editor.spec.ts` still passes.

**Acceptance.**
- ✅ Picking, clearing, choosing from the library and removing a block each update the counter, without reloading.
- ✅ The submit button is never disabled by the counter.
- ✅ The counter is an `aria-live` region and the insufficient state is stated in text.
- ✅ `pnpm run gate` green; `pnpm run e2e` green for the new spec and `multi-editor.spec.ts`.

### 3.2 Visual QA checklist

Filled by VERIFY; written now, while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Create chapter, desktop, confirmed author with 5 credits | Advanced mode: each image field shows the notice with balance 5. Counter reads 1 credit for the chapter + 0 images, then updates to 2 and back as files are picked and cleared | |
| Create chapter, insufficient | 2 credits, 2 files: the counter shows « solde insuffisant » in text; submit is still enabled; after submit the `credits` error appears, the title and text are kept, the files must be re-picked, no chapter is created | |
| Library reuse | Choosing an existing image from the picker leaves the counter unchanged; save spends no credit for it | |
| Edit chapter | An already-saved image does not count; a new pick counts 1; a text-only save at balance 0 succeeds | |
| Edit chapter, insufficient | Rejected with the error; the chapter content is unchanged on reload | |
| Simple mode | Counter shows no pending image; notice not visible (no image fields) | |
| Mobile (375px) | Notice and counter wrap cleanly under the image field / content area, no overflow | |
| Dark mode | Notice and counter text readable | |
| News admin form, StaticPage admin form | Image fields show no credit notice and no counter (regression guard) | |
| Co-author | Co-author editing sees their own balance, and their own credits are spent | |

### 3.3 Open items

- **Phase 1** — the `story::validation` lang file path and the exact key nesting (`chapter.blocks.required` already exists there; follow it).
- **Phase 1** — whether Story's tests have a `Unit/` folder; if not, put the `trySpend` tests in `Tests/Feature/` as DB-backed tests.
- **Phase 3** — the e2e seeder's confirmed author must have at least 3 credits and a story whose create form is reachable. Check `E2eStorySeeder`, and set the credits there if needed.
- **Phase 3** — confirm that `click` on the image-field's delete and library buttons bubbles to the wrapper. Neither uses `.stop` today. If one ever does, the `MutationObserver` alone will not catch an input value change.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-11-02 | REFINE | Unit of charge | 1 credit per newly uploaded file | — |
| 2 | 2026-11-02 | REFINE | What is free | Library reuse, already-saved images, unchanged re-saves; only a fresh file upload costs; no refund for replaced/removed images | — |
| 3 | 2026-11-02 | REFINE | Insufficient credits | All-or-nothing: reject the save with a French validation error, upload and spend nothing; on creation the requirement is 1 + number of new images | — |
| 4 | 2026-11-02 | REFINE | Warning | Inline notice beside the image field (« Importer une image coûte 1 crédit de chapitre, il vous en reste N ») plus live count of pending uploads vs balance; no modal | — |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Only confirmed authors with a story reach the form | REFINE | Yes |
| A2 | Spend happens inside the save transaction via a `trySpendOne` loop | REFINE | Yes |
| A3 | No refund when a chapter is deleted (nor on replace/remove) | REFINE | Yes |
| A4 | No notification | REFINE | Yes |
| A5 | Author-note rich-text editor out of scope | REFINE | Yes |
| A6 | Staff get no exemption | REFINE | Yes |
| A7 | Cost fixed at 1, not configurable | REFINE | Yes |
| A8 | Cost also applies when editing an existing chapter | REFINE | Yes |
| A9 | When the pending total exceeds the balance, the counter appends a textual « solde insuffisant »; it is informational and never blocks | DESIGN | Yes, a single label |
| A10 | No audit event for the spend: no credit movement emits one today (§2.7 #4) | DESIGN | Yes |
| A11 | A2 is superseded: the spend is one guarded `trySpend($n)` `UPDATE` instead of a `trySpendOne` loop; it is also used for the creation credit, replacing the trailing `spendOne()` (§2.7 #1) | DESIGN | Yes |
| A12 | The 0-balance create guard stays a 403 `AuthorizationException` (unreachable from the UI); only the shortfall caused by images is a `credits` validation error (§2.7 #5) | DESIGN | Yes |
| A13 | On a lost concurrent race, already-stored files become orphans left to `media:gc`; nothing is spent and no chapter is written | DESIGN | Yes |
| A14 | The counter lives in a Story Vite module that counts the file inputs from the outside; Media and Editor get no new events (§2.7 #3, #6) | DESIGN | Yes |
| A15 | The error's `:balance` is clamped to 0 (legacy rows can be negative); on a lost race it reports the balance re-read at that moment | BUILD 1 | Yes |
| A16 | Story `AGENTS.md` invariant « check before, spend after » rewritten in phase 1 (it contradicted the new order); WRAP only needs to polish it | BUILD 1 | Yes |

## 5. Build & verify log

- **Phase 1 — DONE (2026-11-02).** `trySpend`, `countNewUploads` (shared `orderedBlocks()` iterator), pre-check before `resolve()` and spend-first in both transactions, `credits` error slot in the form. Tests: `Tests/Feature/Chapters/ChapterImageCreditCostTest.php` (10), `Tests/Feature/ChapterCreditTrySpendTest.php` (4). `pnpm run gate` green (first run hit a transient Blade-cache `tempnam` race in Fanart `ArtworkTest` under parallel; passes alone and on rerun).
- **Phase 2 — DONE (2026-11-02).** Editor `imageNotice` prop (multi + `_image-block` to `helpText`), controller `availableCredits`, Story form wiring and lang key. Tests: 2 in `Editor/Tests/Feature/MultiEditorComponentTest.php`, 1 in `ChapterImageCreditCostTest.php`. `pnpm run gate` green.
