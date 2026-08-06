# Shared `image-upload` lang ownership

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-07-31 (VERIFY skipped, user smoke-checks) ·
**Domain(s):** `Shared`, `Story`

## What it does

Shared shipped `Resources/lang/fr/image-upload.php` for a Blade component that
`shared-image-upload-cleanup/` had already deleted. The file survived only
because Story's `cover-tab-custom.blade.php` borrowed three of its keys for its
own hand-rolled dropzone. Those three strings now live in Story's cover lang as
`story::shared.cover.custom_drop_or_click` / `custom_max_size` /
`custom_size_error`, and the Shared file is gone along with its five keys that no
one used (`preview_alt`, `recommended_dimensions`, `recommended_ratio`,
`delete`, `cancel`). Copy-only: same French wording, same `:size` and `:max`
placeholders, no UI change.

## Key behaviour

- **Shared owns no image concern at all now** — no `image-upload` component, no
  `image-upload` lang. Image handling belongs to Media
  (`<x-media::image-field>`). `<x-shared::sound-upload>` and
  `shared::sound-upload` lang are untouched, pending `media-sound-upload/`.
- The Story cover custom tab is **not** a Media component: it is a bespoke Alpine
  dropzone in `cover-tab-custom.blade.php` that owns its copy. Adding a fourth
  string means adding it under `story::shared.cover.custom_*`, not reaching into
  Shared.
- The 2 MB cover limit is **hard-coded at four sites** and nothing keeps them in
  sync: `StoryRequest` (`max:2048`), the Blade `['size' => 2]`, the JS
  `maxKb = 2048`, and the Blade `['max' => 2]` passed into the error string.
  This predates the task and was left alone.
- The only remaining mentions of `shared::image-upload` under `app/` are the
  **negative** assertions in `UploadComponentsTest` and the warning in Shared's
  `AGENTS.md`. Plan acceptance said "no references"; these are the intended kind.

## Where the code lives

| Concern | Path |
|---------|------|
| Story lang (new keys) | `app/Domains/Story/Private/Resources/lang/fr/shared.php` — `cover.custom_drop_or_click` / `custom_max_size` / `custom_size_error` |
| Story view | `app/Domains/Story/Private/Resources/views/components/cover-tab-custom.blade.php` — three `__()` call sites, one inside `@js()` in the Alpine `checkSize()` |
| Deleted | `app/Domains/Shared/Resources/lang/fr/image-upload.php` |
| Shared docs | `app/Domains/Shared/README.md` (Uploads section), `app/Domains/Shared/AGENTS.md` (non-obvious rules) |
| Tests | `app/Domains/Shared/Tests/Feature/View/Components/UploadComponentsTest.php` |

Commit: `03c7f4b6`. No schema, service, route, JS-bundle or deptrac change.

## Extension points used

None. No events, notifications, settings, statistics, moderation, media usage
provider or deptrac edge.

## Decisions worth remembering

- **Move the keys, do not redesign the cover** (#1). Putting the cover tab on
  `<x-media::image-field>` would delete the bespoke dropzone as well — a UI
  redesign, deliberately out of scope. Still true, still tempting: see
  "Not done".
- **The five unused Shared keys were deleted with the file** (A1), not
  re-homed. Nothing consumed them; git has them if a future widget wants them.
- **Tests assert `Lang::has($key, 'fr')`, never `__()` output.** The suite runs
  under `APP_LOCALE=zz`, where comparing two `__()` results passes vacuously.
  Same trap as the previous task.
- Prefixing with `custom_` matches the existing `custom_description` /
  `custom_dimensions` / `custom_upload_label` keys in the same `cover` block —
  the prefix marks the tab, not the "custom cover" feature flag.

## Plan vs. code

The single phase shipped exactly as planned — same files, same keys, same
deletions. No drift to report.

## Not done

**Deliberate non-goals**: moving the cover tab onto
`<x-media::image-field>`; touching sound upload; changing the French wording.

**VERIFY was skipped** (A2) — copy-only change, the user smoke-checks the Story
cover custom tab by hand. The two-row visual QA checklist (in the retired
`03-plan.md`) is therefore **unfilled**: the custom tab's drop prompt /
max-size hint / oversize error, and SecretGift's sound upload being unchanged.
Note the custom cover tab is behind the `story.custom_covers_enabled` feature
toggle (off by default), so the toggle must be on to see it.

**No e2e spec** was added; `e2e/tests/features/` holds only its README, so
nothing to retire or promote.

**No new backlog rows.** The one candidate — rebuilding the cover custom tab on
`<x-media::image-field>`, which would also collapse the four duplicated 2 MB
limits — was left unfiled: it is a UI change with no current pain, and
`media-sound-upload/` is the more valuable of the two remaining upload cleanups.
File it if the cover copy or the size limit drifts again.

**Superseded:** [`shared-image-upload-cleanup`](./shared-image-upload-cleanup.md)
states the Shared lang file survives its component. It no longer does.
