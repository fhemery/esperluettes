# Shared `image-upload` lang ownership — implementation plan

- Functional: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Move three cover strings into Story; delete Shared lang | S | — | DONE |

## Phase 1 — Move three cover strings into Story; delete Shared lang

**Goal.** Story owns its dropzone copy; Shared `image-upload` lang is gone.

**Deliverables.**
- Add to `app/Domains/Story/Private/Resources/lang/fr/shared.php` under `cover`:
  - `custom_drop_or_click` ← `drop_or_click`
  - `custom_max_size` ← `max_size` (param `:size`)
  - `custom_size_error` ← `size_error` (param `:max`)
  Same French strings as Shared today.
- Update `cover-tab-custom.blade.php` to `story::shared.cover.custom_*`.
- Delete `app/Domains/Shared/Resources/lang/fr/image-upload.php`.
- Update Shared `AGENTS.md` / `README.md` — remove the “lang survives for Story”
  note.
- Adjust `UploadComponentsTest`: Shared image-upload lang must be absent;
  Story keys present via `Lang::has(..., 'fr')`. Keep sound-upload assertions.

**Tests.** Failing first: assert Shared lang gone + Story keys exist + cover
Blade references (or TranslationKeysExistTest covers static keys). Gate green.

**Acceptance.**
- ✅ No `shared::image-upload` references under `app/`
- ✅ Cover tab resolves Story keys
- ✅ `npm run gate` green

## Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Story cover — custom tab | Drop prompt, max-size hint, oversize error still French and sensible | |
| Shared sound-upload (SecretGift) | Unchanged | |

## Open items

None.
