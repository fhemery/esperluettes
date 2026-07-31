# `<x-shared::image-upload>` cleanup — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Collapse upload widgets into SecretGift | S | — | DONE |

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

## Phase 1 — Collapse upload widgets into SecretGift

**Goal.** Move `<x-shared::image-upload>` and `<x-shared::sound-upload>` into
the SecretGift plugin and delete them from Shared, with no change to upload,
serve, remove, or lang resolution.

Depends on architecture §1 (domain placement), §4 (frontend), §8 (file layout).
No prior phases.

**Deliverables.**
- Move (copy then delete originals):
  - `app/Domains/Shared/Resources/views/components/image-upload.blade.php`
    → `app/Domains/Calendar/Private/Activities/SecretGift/Resources/views/components/image-upload.blade.php`
  - `app/Domains/Shared/Resources/views/components/sound-upload.blade.php`
    → `app/Domains/Calendar/Private/Activities/SecretGift/Resources/views/components/sound-upload.blade.php`
- Register anonymous components in
  `app/Domains/Calendar/Private/Activities/SecretGift/SecretGiftServiceProvider.php`:
  `Blade::anonymousComponentPath(…/SecretGift/Resources/views/components, 'secret-gift')`
  (mirror `SharedServiceProvider` pattern — architecture §4, tradeoff #5).
- Update
  `app/Domains/Calendar/Private/Activities/SecretGift/Resources/views/partials/_gift-preparation.blade.php`:
  `<x-shared::image-upload>` → `<x-secret-gift::image-upload>`,
  `<x-shared::sound-upload>` → `<x-secret-gift::sound-upload>` (same props).
- Delete the two Shared component Blade files listed above.
- **Do not touch:** `app/Domains/Shared/Resources/lang/fr/image-upload.php`,
  `app/Domains/Shared/Resources/lang/fr/sound-upload.php`, Story cover tab,
  SecretGift service/controller/routes, or Media.
- Optional regression guard (recommended):
  `app/Domains/Shared/Tests/Unit/SharedUploadComponentsRemovedTest.php` — assert
  the two Shared component files no longer exist and/or that
  `Blade::resolveComponent('shared::image-upload')` throws.

**Tests.**
- Existing (must stay green — architecture §6, acceptance bar):
  - `app/Domains/Calendar/Tests/Feature/SecretGift/SaveGiftTest.php` — all cases
    (text, image upload/replace/remove, sound upload/replace/remove, validation,
    auth).
  - `app/Domains/Calendar/Tests/Feature/SecretGift/ServeFileTest.php` — all
    image/sound serve and 403 timing cases.
- New (optional, write first if added):
  - `SharedUploadComponentsRemovedTest` — Shared orphan components are gone.
- No new Vitest or Playwright (functional §4.3, assumption A4).

**Acceptance.**
- ✅ Gift preparation partial renders via `<x-secret-gift::image-upload>` and
  `<x-secret-gift::sound-upload>`; Shared no longer exposes those tags.
- ✅ `SaveGiftTest` and `ServeFileTest` fully green — upload, replace, remove,
  and auth-gated serve behaviour unchanged.
- ✅ Story cover tab still resolves `shared::image-upload.drop_or_click`,
  `max_size`, and `size_error` (lang files untouched in Shared).
- ✅ Relocated widgets still use `shared::image-upload.*` and
  `shared::sound-upload.*` for widget chrome (no Calendar lang copies).
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Secret Gift preparation — image mode (giver, active activity) | Switch to image gift; drop zone, preview, remove marker, client size error, save — same as before | |
| Secret Gift preparation — sound mode (giver, active activity) | Switch to sound gift; drop zone, audio preview, remove marker, save — same as before | |
| Secret Gift preparation — mobile | Image and sound upload widgets usable on narrow viewport | |
| Story cover tab (author) | Custom cover upload still shows borrowed strings (`drop_or_click`, max size hint, size error) | |
| Secret Gift — recipient before activity ends | Received-gift tab shows “will be revealed”; no image/sound preview leak | |
| Secret Gift — recipient after activity ends | Can view/download giver's image or sound gift | |

## Open items

| Item | Phase | Notes |
|------|-------|-------|
| Alpine helper name collision on a page loading both widgets | 1 | Both widgets already load together on preparation today; keep distinct `x-data` names from current Shared files. Unlikely to break — verify in browser during VERIFY if gate-only BUILD. |
| Shared lang files outlive their components | — | Known non-goal (decision A5/A7). Story borrows three `image-upload` keys; SecretGift widgets borrow full `image-upload` + `sound-upload` sets. Deferred string ownership cleanup. |
