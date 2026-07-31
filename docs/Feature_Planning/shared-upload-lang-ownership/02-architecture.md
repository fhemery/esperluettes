# Shared `image-upload` lang ownership — architecture

- Functional: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Story** owns the three cover dropzone strings under `story::shared.cover`.
**Shared** deletes `Resources/lang/fr/image-upload.php` and drops the AGENTS/README
note about Story borrowing them.

## 2–5. Data / PHP / Frontend / Deptrac

No schema, services, routes, or deptrac changes. Blade updates three `__()` calls
in `cover-tab-custom.blade.php`. Add keys next to existing
`custom_description` / `custom_dimensions` in `Story/.../lang/fr/shared.php`.

## 6. Testing

- Update `UploadComponentsTest` (or Shared test that currently asserts
  `shared::image-upload.*` still exists) — Shared file gone; Story keys present.
- `TranslationKeysExistTest` must stay green (static keys resolve).
- Existing Story cover feature tests if any.

## 7. Tradeoffs locked

| # | Question | Chosen | Why |
|---|----------|--------|-----|
| 1 | Move keys vs redesign cover on Media | Move keys | Request out-of-scope redesign |
| 2 | Keep unused Shared keys | Delete with file | No remaining consumer |

## 8. File layout

Edit only: Story lang + cover Blade; Shared lang delete; Shared AGENTS/README;
Shared upload component test.

## 9. Risks

None material — copy move only.
