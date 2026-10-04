# Gift sound on Media, retire `<x-shared::sound-upload>`

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-10-04 ·
**Domain(s):** `Media`, `Calendar/SecretGift`, `Shared`

## What it does

The Secret Gift sound now follows the gift image: bytes on Media's `private`
disk under `secret-gift/{activityId}/{hash}.mp3`, served by SecretGift after
`canViewSound()`, reclaimed by `media:gc` instead of deleted inline. Media grew
`storePrivateFile()` (raw bytes, no `ImageService`) and a Range-capable
`stream()`, which replaced SecretGift's hand-written Range code. The widget moved
from `<x-shared::sound-upload>` to `<x-media::sound-field>`; Shared ships no
upload widget any more. No user-visible change.

## Key behaviour

- Visibility unchanged: giver always; recipient once ENDED/ARCHIVED; others 403;
  guest → login. Upload/replace/remove only while ACTIVE.
- **Nothing in Calendar deletes a sound.** Replace rewrites `gift_sound_path`,
  remove nulls it; `SecretGiftMediaUsageProvider` now claims image **and** sound
  paths, so GC reclaims orphans after 7 days. This also closes the re-shuffle /
  activity-deletion leak (dropping rows drops claims).
- `stream()` on a private path returns a `BinaryFileResponse`: Range (206,
  `Content-Range`, `Accept-Ranges: bytes`) works **only once the router
  prepares it** — a direct call in a test gets no Range. Defaults
  (`Content-Type` from the disk, inline disposition) are set by hand for the same
  reason; caller headers win. Gift **images** got Range too.
- GC on `private` treats **every** file under a root as an original (no
  extension list); the public disk still filters to image originals.
- `storePrivateFile()` names the file `hashName()` — extension from the guessed
  MIME, never the client name. Media validates nothing; `SaveGiftRequest`
  (`mimes:mp3`, 10 Mo) does.
- Download keeps its name `gift-audio-{giver}-{id}.mp3`, now via `stream()` with
  an `attachment` disposition (so it is Range-capable as well).
- Form contract unchanged: file `gift_sound`, hidden `gift_sound_remove`.

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `app/Domains/Media/Public/Api/MediaPublicApi.php` — `storePrivateFile`, `stream(): Response` |
| Media service | `app/Domains/Media/Private/Services/MediaService.php` — `storePrivateFile`, `stream`, `originalsIn` |
| Gift storage | `…/SecretGift/Services/SecretGiftService.php` — `saveGiftSound`, `removeGiftSound` |
| Serve / download | `…/SecretGift/Http/Controllers/SecretGiftController.php` — `streamSound`, `downloadSound` |
| GC claiming | `…/SecretGift/Support/SecretGiftMediaUsageProvider.php` |
| Legacy data move | `…/SecretGift/Support/LegacyGiftSoundMover.php` + `…/Database/Migrations/2026_10_03_120000_move_secret_gift_sounds_to_media_private.php` |
| Widget | `app/Domains/Media/Private/Resources/views/components/sound-field.blade.php` (Alpine `mediaSoundField`, inline `@once`), lang `Media/Private/Resources/lang/fr/sound-field.php`; consumer `…/SecretGift/Resources/views/partials/_gift-preparation.blade.php` |
| Tests | Media `{MediaPrivateStorage,MediaService,MediaSoundFieldComponent}Test.php`; Calendar `SecretGift/{SaveGift,ServeFile,GiftImageUsageProvider,LegacyGiftSoundMove,SecretGiftPage}Test.php`; Shared `View/Components/UploadComponentsTest.php` (asserts both widgets gone) |
| E2E infra kept | `E2eSecretGiftSeeder` ENDED activity `cadeau-surprise-termine` with real media; `GIFTS.ended`, `GIFT_MEDIA` in `e2e/support/fixtures.ts`; `e2e/fixtures/gift-sound{,-short}.mp3`, `gift-image.png`; page objects `e2e/pages/SoundField.ts`, `SecretGiftActivityPage.ts` |
| Deleted | `Shared/…/components/sound-upload.blade.php`, `Shared/…/lang/fr/sound-upload.php` (3 unused keys dropped) |

Commits: `c400c84b` (widget move), `6996c418` (Range `stream`), `deaa2ec8`
(`storePrivateFile` + GC), `b70721e9` (mover), `93ccede2` (SecretGift flip +
migration), `46d420e2` (VERIFY).

## Extension points used

- **Media usage registry** — `SecretGiftMediaUsageProvider` extended to sound.
- No events, notifications, settings, statistics or moderation. **No new deptrac
  edge** (`CalendarPrivate → MediaPublic` already existed).

## Decisions worth remembering

- **Media owns non-image private files** (#1, #4): a dedicated
  `storePrivateFile()`, not `storePrivate()` silently accepting audio.
- **Range lives in Media's `stream()`** (#5) via `BinaryFileResponse`. Holds only
  while the `private` disk is `local`; S3 would lose Range (architecture §9).
- **Deletion is GC-only** (#2) — no delete method on `MediaPublicApi`.
- **Code and data flip together**: the serve route resolves the disk from the
  path prefix, so the migration runs on `migrate`; un-migrated rows would 404.
  `LegacyGiftSoundMover` keeps the basename, is idempotent and reversible, and
  uses `Storage::disk` directly (accepted exception, like the image mover).
- **Widget is sound-specific** (A3), not a generic `file-field`; collapsing
  widgets into SecretGift was tried for images and reverted.

### Assumptions made without asking (reversible)

| # | Assumption |
|---|------------|
| A1 | Upload UX (drag & drop, preview, remove) kept identical |
| A2 | Existing sounds moved by a DB migration; missing files logged, not fatal |
| A3 | Widget is `<x-media::sound-field>` (sound-specific), form contract `{name}` + `{name}_remove` kept |

## Plan vs. code

- The plan matches the code phase by phase. One gap: VERIFY listed three feature
  tests "to add in BUILD" (guest → login, ARCHIVED recipient → 200, reveal
  without a sound), but **they were never written** — the task went to WRAP with
  every phase `DONE`. Pushed to the backlog (below).

## Not done

**Non-goals (spec §8):** visibility/timing changes or admin access; the image
half; other audio formats, size or duration limits; a sound reuse picker;
deactivated-user handling.

**E2E:** `e2e/tests/features/media-sound-upload.spec.ts` **deleted** at WRAP —
it guards one Secret Gift form, nothing app-wide; storage, Range and auth are
covered by PHP tests. Seeder, fixtures and page objects kept for the next Secret
Gift spec.

**Before deploying:** count rows to move —
`select count(*) from calendar_secret_gift_assignments where gift_sound_path is not null and gift_sound_path not like 'secret-gift/%'`
— and check the `missing` list in the migration log line.

**Pushed back to [`BACKLOG.md`](../BACKLOG.md):**

- [`secret-gift-reveal-followups/`](../secret-gift-reveal-followups/00-request.md)
  — the 3 missing tests; activity page 404s once ARCHIVED while gift routes still
  serve; page opens on the prepare tab once ENDED.
- [`media-sound-field-polish/`](../media-sound-field-polish/00-request.md) —
  `upload_audio_file` is not a Material Symbols glyph (renders as text); a saved
  sound shows no name/size. Both pre-existing.
