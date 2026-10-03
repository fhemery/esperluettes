# Gift sound on Media — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md) — settled, do not reopen.

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Refactor — move the sound widget from Shared to Media (`<x-media::sound-field>`) | S | — | TODO |
| 1v | Checkpoint — gift preparation form (sound tab) | S | 1 | TODO |
| 2 | Media — Range-capable `stream()` for private paths (shared infra) | S | — | TODO |
| 2v | Checkpoint — gift image view / download | S | 2 | TODO |
| 3 | Media — raw private file store `storePrivateFile()` + GC treats every private file as an original (shared infra) | S | — | TODO |
| 4 | SecretGift — `LegacyGiftSoundMover` (class + tests, not wired yet) | S | — | TODO |
| 5 | SecretGift — sound on Media: store, claim, serve, migrate existing files | M | 2, 3, 4 | TODO |

Total: **7 rows** (5 phases + 2 checkpoints).

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

Why this order. Phase 1 is a pure move with no behaviour change (the widget
already receives the sound URL from a route; storage is irrelevant to it).
Phases 2 and 3 are Media infrastructure; 2 touches the existing gift-image
endpoint, hence its checkpoint. Phase 4 adds the mover as dead code so phase 5
can flip code **and** data together — the serve route resolves the disk from the
path prefix, so switching the controller without migrating the rows (or the
reverse) would 404 every existing sound. Phase 5 is therefore indivisible.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- No Python / sed / awk / heredocs to edit files — Edit/Write tools only.

---

## Phase 1 — Refactor: move the sound widget from Shared to Media

**Goal.** `<x-shared::sound-upload>` becomes `<x-media::sound-field>` with
identical behaviour and form contract; Shared no longer ships a sound widget.
No behaviour change for users.

Architecture: §4 (component shape, props, form contract, lang), §1.1 (Shared).

**Context.** Today the only consumer is
`app/Domains/Calendar/Private/Activities/SecretGift/Resources/views/partials/_gift-preparation.blade.php`
(≈ line 128), which passes `name="gift_sound"`, `label`, `currentUrl` (already
`route('secret-gift.sound', …)` or null), `currentPath`, and `helpText`. The
`currentPath` / `disk` / `asset('storage/…')` fallback is dead in practice —
`currentUrl` always wins. The controller reads `gift_sound` (file) and
`gift_sound_remove` (hidden boolean); both names must stay.

**Deliverables.**
- Create `app/Domains/Media/Private/Resources/views/components/sound-field.blade.php`
  — a move of `app/Domains/Shared/Resources/views/components/sound-upload.blade.php`:
  - Props: `name`, `id` (default `null`), `previewUrl` (default `null`),
    `maxSize` (default `10240`), `accept` (default `'audio/mp3'`),
    `removable` (default `true`), `label`, `helpText`. **Dropped:** `disk`,
    `currentPath`, `currentUrl`, and the `asset('storage/…')` fallback — the
    component never builds a URL.
  - Default id prefix `sound-field-` instead of `sound-upload-`.
  - Same markup (drop zone, `<audio controls preload="metadata">`, delete /
    cancel buttons, file input `name="{{ $name }}"`, hidden
    `name="{{ $name }}_remove"`), still using `<x-shared::input-label>` /
    `<x-shared::input-error>` (as `image-field` does).
  - Alpine component renamed `soundUpload` → `mediaSoundField`, same
    `@once @push('scripts')` inline registration, logic unchanged
    (`hasCurrentSound` derived from `previewUrl`).
  - All `__('shared::sound-upload.*')` → `__('media::sound-field.*')`.
- Create `app/Domains/Media/Private/Resources/lang/fr/sound-field.php` with the
  six used keys, wording unchanged: `drop_or_click`, `allowed_formats`,
  `max_size`, `delete`, `cancel`, `browser_no_support`. The three unused keys
  (`preview_alt`, `upload_audio_file`, `audio_file`) are **not** carried over.
- Edit `_gift-preparation.blade.php`: `<x-shared::sound-upload …>` →
  `<x-media::sound-field …>`; `:currentUrl=` → `:previewUrl=`; remove the
  `:currentPath=` line. Other props unchanged.
- Delete `app/Domains/Shared/Resources/views/components/sound-upload.blade.php`
  and `app/Domains/Shared/Resources/lang/fr/sound-upload.php`.
- Edit `app/Domains/Shared/Tests/Feature/View/Components/UploadComponentsTest.php`:
  replace `it('still ships the sound upload component')` by the negative
  pair, mirroring the existing image ones (see Tests).
- Docs:
  - `app/Domains/Shared/README.md` (≈ line 238) and `app/Domains/Shared/AGENTS.md`
    (≈ line 65): Shared ships **no** upload widget any more; sound lives in
    Media's `<x-media::sound-field>`. Do not reintroduce one here.
  - `app/Domains/Media/README.md` → "Components": add `<x-media::sound-field>`
    (props, form contract `{name}` + `{name}_remove`, no URL building — consumer
    passes `previewUrl`).

**Tests.**
- `app/Domains/Media/Tests/Feature/MediaSoundFieldComponentTest.php` (new),
  `describe('<x-media::sound-field>')`:
  - `it('renders the file input and the remove flag under the given name')` —
    `name="gift_sound"` and `name="gift_sound_remove"` present.
  - `it('uses the consumer supplied preview url as the current sound')` —
    `previewUrl="/x/sound/1"` appears in the Alpine init payload.
  - `it('renders the empty drop zone copy when there is no preview url')` —
    `__('media::sound-field.drop_or_click')` and the max-size line
    (`10` Mo for the default).
  - `it('hides the delete control when removable is false')`.
  - `it('never builds a storage url')` — output contains no `/storage/`.
- `UploadComponentsTest.php`:
  - `it('no longer ships the sound upload component')` — file absent, and
    `<x-shared::sound-upload name="gift_sound" />` throws
    `InvalidArgumentException`.
  - `it('no longer ships the sound upload lang file')` — file absent,
    `Lang::has('shared::sound-upload.drop_or_click', 'fr')` false.
- Existing `app/Domains/Calendar/Tests/Feature/SecretGift/*` stay green
  unchanged (the form contract did not move). If a Calendar view test renders
  the preparation partial, add one assertion that it contains
  `name="gift_sound_remove"`; otherwise none is needed.

**Acceptance.**
- ✅ `grep -rn "sound-upload\|soundUpload" app/ resources/ e2e/` returns nothing.
- ✅ The gift preparation page renders the sound tab with the same drop zone,
  player and remove button as before (checked in 1v).
- ✅ Submitting the form still sends `gift_sound` / `gift_sound_remove`; all
  `SaveGiftTest` sound tests pass unchanged.
- ✅ `pnpm run gate` green.

---

## Checkpoint 1v — gift preparation form (sound tab)

Run by `visual-verifier`. Changes no code.

Look at, as a confirmed participant who is a **giver** in an **ACTIVE**,
shuffled Secret Gift activity (calendar activity page → gift preparation):
- Sound tab, no sound yet: dashed drop zone, "Glissez-déposez…", "Format
  accepté : MP3", "Taille maximale : 10 Mo".
- Pick an mp3 via the file picker: preview player appears with file name and
  size, cancel (×) button restores the empty state.
- Drag & drop an mp3: same preview.
- Save, reload: current sound plays in the player; delete button shown; delete
  → drop zone back; save → sound gone.
- Mobile width (≈ 390 px): layout not broken.
- Browser console: no Alpine error (`mediaSoundField` defined once).

E2E: `pnpm run e2e -- e2e/tests/core/confirm-modal.spec.ts` (drives the Secret
Gift screens).

Output: screenshots under `shots/checkpoint-1v/`, then a
`**Result (<date>, HEAD <sha>) — PASS|FAIL**` block appended here.

---

## Phase 2 — Media: Range-capable `stream()` for private paths

**Goal.** `MediaPublicApi::stream()` answers HTTP Range requests for private
paths, so any consumer (gift image now, gift sound in phase 5) gets seeking for
free. Public-path behaviour unchanged.

Architecture: §3.1 (`stream()` signature), §3.2 (`MediaService::stream`),
§7 tradeoff 2, §9 risks.

**Shared infrastructure.** The existing consumer is
`SecretGiftController::serveImage()` (routes `secret-gift.image` and
`secret-gift.download-image`); it must keep working unchanged.

**Deliverables.**
- `app/Domains/Media/Private/Services/MediaService.php` — `stream()`:
  - Return type `Symfony\Component\HttpFoundation\Response`.
  - Private path (`isPrivatePath`): build a
    `Symfony\Component\HttpFoundation\BinaryFileResponse` from
    `Storage::disk(self::PRIVATE_DISK)->path($path)`, status 200. Set defaults
    **explicitly**, because Range handling and the default `Content-Type` only
    happen in `prepare()`, which the router calls but a direct call does not:
    `Content-Type` = `Storage::disk('private')->mimeType($path)` (fallback
    `application/octet-stream`), `Content-Disposition` = inline with the
    basename (parity with today's `Storage::response`), then apply `$headers`
    so caller values win. Do not set `Content-Length` by hand —
    `BinaryFileResponse::prepare()` computes it (and the ranged length).
  - Public path: unchanged (`Storage::disk('public')->response(...)`).
- `app/Domains/Media/Public/Api/MediaPublicApi.php` — `stream()` return type
  widened to `Symfony\Component\HttpFoundation\Response`; docblock: private
  paths come back as a `BinaryFileResponse` that honours `Range` (206 /
  `Content-Range` / `Accept-Ranges: bytes`) once prepared by the router; still
  no authorization.
- `app/Domains/Media/Tests/Feature/MediaPrivateStorageTest.php` — the existing
  `it('streams a private image back with its mime type')` asserts
  `toBeInstanceOf(StreamedResponse::class)`: change to
  `BinaryFileResponse::class`. Body check via `sendContent()` still works.
- `app/Domains/Media/README.md` → "Private images": `stream()` returns a
  `BinaryFileResponse` for private paths and supports Range; note the §9 risk
  (relies on the private disk being `local`).

**Tests.**
- In `MediaPrivateStorageTest.php`, `describe('stream')`:
  - `it('streams a private image back with its mime type')` (adjusted type).
  - `it('merges caller supplied headers into the stream response')` (unchanged,
    must stay green — Content-Type `image/jpeg` without `prepare()`).
  - `it('defaults to an inline disposition under the stored basename')`.
- Range must be proven **through the router** (a direct `stream()` call is not
  prepared). Add to
  `app/Domains/Calendar/Tests/Feature/SecretGift/ServeFileTest.php`,
  `describe('Serve Image')` — the gift image is the only routed private stream
  today:
  - `it('answers a Range request on the image with 206 and Content-Range')` —
    giver, `withHeaders(['Range' => 'bytes=0-9'])`, assert 206,
    `Content-Range: bytes 0-9/<size>`, `streamedContent()` length 10.
  - `it('advertises byte ranges on a plain image request')` — 200 and
    `Accept-Ranges: bytes`.
  - `it('still refuses the image to a recipient before the end even with a Range header')`
    — 403 (authorization runs before Media).
- Existing `Serve Image` tests (incl. `streams the image bytes from the private
  disk`, `sends a download disposition on the download route`) and
  `LegacyGiftImageMoveTest` stay green — `TestResponse::streamedContent()`
  accepts a `BinaryFileResponse`.

**Acceptance.**
- ✅ `GET secret-gift.image` with `Range: bytes=0-9` as the giver → 206,
  `Content-Range: bytes 0-9/N`, 10-byte body.
- ✅ Same route without Range → 200, `Accept-Ranges: bytes`, full body.
- ✅ Download route still sends `attachment; filename="gift-image-{giver}-{id}.{ext}"`.
- ✅ A recipient before the activity ends still gets 403.
- ✅ Public-path `stream()` unchanged (no public test changes).
- ✅ `pnpm run gate` green.

---

## Checkpoint 2v — gift image view / download

Run by `visual-verifier`. Changes no code.

- As a **giver** in an ACTIVE activity with an uploaded gift image: the image
  preview shows in the preparation form (served by `secret-gift.image`).
- As the **recipient** once the activity is ENDED: the reveal shows the image;
  the download button saves a file named `gift-image-…`, which opens.
- As the recipient while ACTIVE: direct hit on the image URL → 403.
- DevTools network: image response has `Accept-Ranges: bytes`.

E2E: `pnpm run e2e -- e2e/tests/core/confirm-modal.spec.ts`.

Output: screenshots under `shots/checkpoint-2v/`, then the Result block here.

---

## Phase 3 — Media: raw private file store + GC on all private files

**Goal.** Media can store a non-image file on its private disk, and `media:gc`
treats every file under a private root as an original, so a raw mp3 is
collectable with no extension list.

Architecture: §1.1 (Media bullets), §3.1 (`storePrivateFile`), §3.2, §7
tradeoff 1, §9 (GC risk).

**Context.** Today `storePrivate()` routes through `ImageService`; GC's
`originalsIn()` keeps only `jpg|jpeg|png|webp` non-variant files on **both**
disks. No consumer stores non-images yet (phase 5 will), so this phase changes
nothing observable for existing data: the private disk only holds gift images.

**Deliverables.**
- `app/Domains/Media/Private/Services/MediaService.php`:
  - `public function storePrivateFile(string $scope, UploadedFile $file): string`
    — throws `InvalidArgumentException` if `!isPrivateScope($scope)`; otherwise
    `Storage::disk(self::PRIVATE_DISK)->putFileAs($this->folderFor($scope), $file, $file->hashName())`
    and returns the path. No `ImageService`. Extension comes from
    `hashName()` (guessed MIME), never the client name.
  - `originalsIn()`: on `PRIVATE_DISK` return **all** files from `allFiles()`
    (no variant / image-extension filter); public disk filtering unchanged.
    Update its docblock and `gc()`'s ("originals" on private = every file).
  - `gc()` deletion: keep `deleteWithVariants($disk, $path)` (harmless for a
    file with no variants) or call `Storage::disk($disk)->delete()` for the
    private disk — implementer's choice, no behaviour difference.
- `app/Domains/Media/Public/Api/MediaPublicApi.php` — add
  `storePrivateFile(string $scope, UploadedFile $file): string` delegating,
  with a docblock: raw bytes, no processing, no URL, no variants, no content
  validation (the caller validates), served via `stream()`, collected by GC.
  Update the class docblock ("managed images" → images and private files).
- `app/Domains/Media/README.md` → "Private images": rename/extend to private
  files; add a `storePrivateFile` column/row; GC paragraph: on `private` every
  file under a root is an original (no variants by construction).
- `app/Domains/Media/AGENTS.md` — only if it states Media is image-only or
  lists the API; keep it consistent.

**Tests.**
- `app/Domains/Media/Tests/Feature/MediaPrivateStorageTest.php`,
  new `describe('storePrivateFile')`:
  - `it('stores raw bytes on the private disk under the scope with the guessed extension')`
    — `UploadedFile::fake()->create('song.mp3', 100, 'audio/mpeg')` → path
    starts `secret-gift/7/`, ends `.mp3`, exists on `private`, bytes identical
    to the upload, nothing on `public`.
  - `it('does not take the extension from the client file name')` — fake file
    named `evil.php` with MIME `audio/mpeg` → stored path ends `.mp3`.
  - `it('rejects a public scope')` — `storePrivateFile('news', …)` throws,
    nothing written.
- `app/Domains/Media/Tests/Feature/MediaServiceTest.php`, `describe('gc')`:
  - `it('garbage collects an unclaimed private mp3 past the grace window')`.
  - `it('keeps a claimed private mp3')`.
  - `it('keeps a private mp3 inside the grace window')`.
  - `it('still skips the private root when only mp3 files sit under it unclaimed')`
    — zero-claim guard.
  - `it('still ignores non-image files on the public disk')` — e.g.
    `news/readme.txt` unclaimed & old is not deleted (public filter unchanged).
  - Existing private-image GC tests stay green.

**Acceptance.**
- ✅ `storePrivateFile('secret-gift/7', mp3)` → `secret-gift/7/<40 chars>.mp3`
  on `private`, byte-identical.
- ✅ `storePrivateFile('news', …)` throws and writes nothing.
- ✅ `media:gc` deletes an unclaimed, old `secret-gift/…/x.mp3`; keeps a
  claimed one and a young one; skips the root when nothing is claimed.
- ✅ Public-disk GC behaviour unchanged.
- ✅ `pnpm run gate` green.

---

## Phase 4 — SecretGift: `LegacyGiftSoundMover`

**Goal.** An idempotent, reversible mover that relocates legacy gift sounds
from `local:calendar/secret-gift/{activity}/{basename}` to
`private:secret-gift/{activity}/{basename}` and rewrites
`gift_sound_path`. Not wired to any migration yet — dead code until phase 5.

Architecture: §2.1, §2.3 ("Legacy migration").

**Context.** Sibling of
`app/Domains/Calendar/Private/Activities/SecretGift/Support/LegacyGiftImageMover.php`
— read it and mirror it: same `toMedia()` / `toLegacy()` shape, same
`{moved, already_migrated, missing}` report, rows already on the target prefix
counted not moved, missing source reported by assignment id (not fatal), copy →
rewrite row → delete source. Uses `Storage::disk` directly (the accepted
exception). Legacy sound basenames look like `sound-{giver}-{timestamp}.mp3`;
the basename is kept (no rehash).

**Deliverables.**
- `app/Domains/Calendar/Private/Activities/SecretGift/Support/LegacyGiftSoundMover.php`
  (`final class`), operating on `gift_sound_path`. Copy the image mover rather
  than generalising it — the two are one-shot and the image one is already
  shipped; a shared base would be speculative.
- Docblock states it is the only place in SecretGift that touches a sound file.

**Tests.**
- `app/Domains/Calendar/Tests/Feature/SecretGift/LegacyGiftSoundMoveTest.php`
  (fake `local` and `private`; reuse `alice`/`bob`,
  `createShuffledSecretGift`, `getSecretGiftAssignmentAsGiver`; local helper
  `giveLegacyGiftSound()` — name must not clash with `giveLegacyGiftImage()`,
  Pest helpers are global):
  - `it('moves a legacy local gift sound onto the private disk and rewrites the row')`
    — path becomes `secret-gift/{activity}/sound-{giver}-{ts}.mp3`, bytes
    identical, source gone, `moved` = 1.
  - `it('is idempotent')` — second run: `already_migrated` = 1, `moved` = 0,
    path unchanged.
  - `it('reports a row whose file is missing without failing')` —
    `missing` = `[assignment_id => legacy path]`, row untouched.
  - `it('leaves rows without a sound alone')`.
  - `it('moves sounds back on toLegacy')` — round-trip restores the original
    `calendar/secret-gift/…` path and bytes on `local`.
  - `it('does not touch gift image paths')` — a row with both image and sound
    legacy paths: only `gift_sound_path` changes.

**Acceptance.**
- ✅ All the above pass; `LegacyGiftImageMoveTest` still green.
- ✅ No caller references the new class yet (no behaviour change).
- ✅ `pnpm run gate` green.

---

## Phase 5 — SecretGift: sound on Media (store, claim, serve, migrate)

**Goal.** Gift sounds are stored, served and garbage-collected through Media's
private half; existing sounds are migrated on deploy; SecretGift no longer
touches a disk for sound outside the mover.

Architecture: §1.1 (Calendar / SecretGift), §2.3, §3.2 (SecretGift bullet),
§3.3, §3.5, §6 (SecretGift + Mover bullets).

**Context — what earlier phases left.**
- `MediaPublicApi::storePrivateFile(string $scope, UploadedFile $file): string`
  exists: raw private store, path `secret-gift/{id}/{hash}.mp3`, throws on a
  public scope (phase 3).
- `MediaPublicApi::stream($path, $headers)` returns a `BinaryFileResponse` for
  private paths that honours Range once the router prepares it; caller headers
  win (phase 2). `MediaPublicApi::exists($path)` resolves the disk from the
  path prefix.
- Media GC treats every file under `private:secret-gift/` as an original; the
  root is skipped if no provider claims anything under it (phase 3).
- `Support/LegacyGiftSoundMover` exists with `toMedia()` / `toLegacy()`
  (phase 4).
- The form widget is `<x-media::sound-field>`; field names `gift_sound` /
  `gift_sound_remove` are unchanged (phase 1). `SaveGiftRequest` is unchanged.

Code and data must flip in this one phase: the controller resolves the disk
from the path prefix, so un-migrated `calendar/secret-gift/…` rows would 404.

**Deliverables.** (paths under `app/Domains/Calendar/Private/Activities/SecretGift/`)
- `Services/SecretGiftService.php`:
  - `saveGiftSound()` → `$path = $this->media->storePrivateFile('secret-gift/' . $assignment->activity_id, $file)`,
    write column, save, return path. **No delete** of the previous file.
    Docblock as for `saveGiftImage`.
  - `removeGiftSound()` → only nulls the column (mirror `removeGiftImage`).
  - Drop the `Storage` import if now unused.
- `Http/Controllers/SecretGiftController.php`:
  - `streamSound()`: keep `$activity->refresh()`; `canViewSound` → 403; null
    column or `!$this->media->exists($path)` → 404; return
    `$this->media->stream($path, ['Content-Type' => 'audio/mpeg', 'Cache-Control' => 'private, max-age=3600'])`.
    Delete the whole hand-written Range block.
  - `downloadSound()`: same checks; return `$this->media->stream($path, [...,
    'Content-Disposition' => 'attachment; filename="gift-audio-{giver}-{id}.mp3"'])`
    — keep the extension from `pathinfo($path, PATHINFO_EXTENSION)` as today.
  - Remove now-unused imports (`File`, `Storage`).
- `Support/SecretGiftMediaUsageProvider.php`: claim
  `gift_sound_path` as well as `gift_image_path` (non-null values of both
  columns, one query or two); update the docblock (drop "sounds are not
  reported").
- `Database/Migrations/2026_10_03_120000_move_secret_gift_sounds_to_media_private.php`
  — data-only, mirrors `2026_07_31_143000_move_secret_gift_images_to_media_private.php`:
  `up()` logs `LegacyGiftSoundMover::toMedia()` report via `Log::info`,
  `down()` logs `toLegacy()`.
- Docs — `app/Domains/Calendar/Private/Activities/SecretGift/README.md`,
  "Gift assets are never publicly addressable": image and sound now take the
  **same** route (Media private disk, `storePrivate` / `storePrivateFile`,
  `stream()` after `canViewImage`/`canViewSound`, never deleted by Calendar,
  claimed by the provider); remove the "*Sound* is still a raw file on the
  `local` disk…" paragraph; mention the sound migration + `LegacyGiftSoundMover`
  next to the image one. Media README needs no further change (phase 2/3 did it).

**Tests.** Update
`app/Domains/Calendar/Tests/Feature/SecretGift/SaveGiftTest.php` (sound tests
switch from `local` to `private` assertions; its `beforeEach` already fakes
`local`, `private` and `public`):
- `it('allows a participant to upload a sound gift')` — path starts
  `secret-gift/{activity}/`, ends `.mp3`, exists on `private`; nothing new on
  `local`.
- `it('allows removing a sound gift')` — column null, **old file still on
  `private`** (GC's job).
- `it('replaces sound file when uploading a new one')` — new path differs,
  **both** files on `private`; drop the `sleep(1)` (hash names differ).
- `it('validates sound file type')`, `it('validates sound file size')`,
  `it('allows saving text and sound together')` — unchanged, still green.
- `it('refuses a sound upload when the activity is not active')` — add only if
  no existing test covers the sound field for a non-ACTIVE activity; nothing
  stored.

Update `app/Domains/Calendar/Tests/Feature/SecretGift/ServeFileTest.php`,
`describe('Serve Sound')` (existing 403/404 tests switch to `private`; the
"file does not exist" test deletes from `private`):
- Existing: giver 200, recipient-before-end 403, recipient-after-end 200,
  non-participant 403, missing file 404, no sound 404 — all green.
- `it('answers a Range request on the sound with 206 and Content-Range')`.
- `it('advertises byte ranges and audio/mpeg on a plain sound request')`.
- `it('downloads the sound under gift-audio-{giver}-{id}.mp3')` —
  `Content-Disposition` attachment with that exact name, body = stored bytes.
- `it('refuses the sound download to a non-participant')` — 403.
- `it('never exposes a gift sound under a public storage url')` — mirror the
  image one: stored path not on `public`, `/storage/{path}` not served.

Extend the existing
`app/Domains/Calendar/Tests/Feature/SecretGift/GiftImageUsageProviderTest.php`
(it already covers the image claims; add a sound `describe` — renaming the
file is optional and not required):
- `it('claims gift sound paths')`.
- existing image-claim tests stay green.
- `it('stops claiming a sound once it is removed')`.
- `it('lets media gc reclaim a replaced sound but keep the current one')` —
  upload, replace, backdate the old file's mtime (or call `gc(-1)`), run
  `MediaService`/`media:gc`: old deleted, current kept.
- `it('lets media gc reclaim sounds of a re-shuffled activity')` — rows
  deleted by a re-shuffle (or by deleting the assignments) while another
  activity still claims a path → orphan sound collected.

Migration wiring: one test in
`app/Domains/Calendar/Tests/Feature/SecretGift/LegacyGiftSoundMoveTest.php`:
- `it('serves a migrated legacy sound through the sound route')` — legacy
  file on `local`, run `toMedia()`, giver GETs `secret-gift.sound` → 200,
  `streamedContent()` = legacy bytes.

**Acceptance.**
- ✅ Uploading a sound writes `private:secret-gift/{activity}/{hash}.mp3` and
  nothing on `local`.
- ✅ Replace / remove never delete a file; the column is rewritten / nulled.
- ✅ `SecretGiftMediaUsageProvider::usedPaths()` includes every non-null
  `gift_sound_path`.
- ✅ Giver GET `secret-gift.sound` with `Range: bytes=0-9` → 206 +
  `Content-Range`; without → 200 + `Accept-Ranges: bytes` + `audio/mpeg`.
- ✅ Download → `attachment; filename="gift-audio-{giver}-{id}.mp3"`.
- ✅ Recipient before end / non-participant → 403 on both routes; missing
  file or null column → 404.
- ✅ `grep -n "Storage::\|File::" .../SecretGift/Http/Controllers/SecretGiftController.php .../SecretGift/Services/SecretGiftService.php`
  → nothing sound-related left.
- ✅ `php artisan migrate` then `migrate:rollback --step=1` round-trips on a DB
  with a legacy sound (covered by the mover tests; no manual step).
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh. Roles: **giver** = confirmed participant
preparing a gift; **recipient** = the participant who receives it.

| Surface | Check | OK? |
|---------|-------|-----|
| Gift preparation, sound tab, giver, ACTIVE, no sound (desktop) | Empty drop zone, MP3 / 10 Mo copy, label + help text from SecretGift | |
| Same, file picker | Choosing an mp3 shows the preview player with name + size; × cancels back to empty | |
| Same, drag & drop | Dropping an mp3 shows the preview; dropping a non-audio file is ignored | |
| Same, save new sound | Success flash; after reload the current sound plays from the sound route | |
| Same, seek in the preparation player | Dragging the scrubber jumps mid-track and plays from there | |
| Same, replace | Upload a different mp3 → save → reload: the new one plays | |
| Same, remove | Delete → save → reload: drop zone back, gift mode falls back correctly | |
| Same, invalid file | A `.wav` or > 10 Mo file → French validation error under the field, nothing saved | |
| Same, mobile (≈ 390 px) | Drop zone and player fit, buttons tappable, no overflow | |
| Reveal, recipient, ENDED | Sound card shows with player; plays | |
| Reveal, recipient, seek | Scrubbing mid-track works (Range) — DevTools shows a 206 on the sound request | |
| Reveal, recipient, download | File saved as `gift-audio-{giver}-{id}.mp3` and plays locally | |
| Reveal, recipient, ARCHIVED | Same as ENDED (player + download) | |
| Recipient while ACTIVE | Direct GET of the sound / download URL → 403 | |
| Non-participant | Direct GET of the sound / download URL → 403 | |
| Guest | Sound URL → redirect to login | |
| Gift with no sound | Reveal shows no sound card; sound URL → 404 | |
| Migrated legacy sound | A sound uploaded before deploy (seed a `local` file + row, run the migration) plays and downloads in reveal | |
| Gift image (regression) | Image preview in preparation and image + download in reveal still work | |
| Browser console | No Alpine/JS errors on the preparation page | |

## Open items

None blocks BUILD. Verified while planning:

- `TestResponse::streamedContent()` accepts a `BinaryFileResponse` (Laravel
  vendor code checked) — existing image-serve tests survive phase 2.
- `MediaPrivateStorageTest` asserts `StreamedResponse` for a private stream —
  must be updated in phase 2 (listed there).
- The `private` disk is a `local` driver (`config/filesystems.php`), so
  `->path()` and `BinaryFileResponse` work, including under `Storage::fake`.
- `UploadedFile::fake()->create('x.mp3', …, 'audio/mpeg')->hashName()` ends
  in `.mp3` (testing `File` reports the given MIME), and `SaveGiftRequest`'s
  `mimes:mp3` already guarantees the guessed extension in production.
- Assignment rows cascade on activity deletion
  (`cascadeOnDelete` on `activity_id`) — dropping rows drops the claims.
- The only consumer of `<x-shared::sound-upload>` is `_gift-preparation.blade.php`;
  no e2e page object references sound selectors.

To watch, not blocking:

- **Phase 2 — `BinaryFileResponse` defaults outside `prepare()`.** Content-Type
  and disposition must be set explicitly in `MediaService::stream()` or the
  existing direct-call test (`Content-Type: image/jpeg`) fails. Range itself
  is only exercised through routed tests.
- **Phase 5 — mp3 MIME sniffing.** `storePrivateFile` takes the extension from
  the guessed MIME. A real mp3 that `finfo` reports as
  `application/octet-stream` would already be rejected by `mimes:mp3` today,
  so no new failure mode — but VERIFY should upload a real mp3, not just a
  fake.
- **Phase 5 — pre-deploy row count** (architecture §9): not checked in BUILD;
  the migration's `missing` log entry is the signal.
- **No Secret Gift e2e spec covers the sound field.** Checkpoints and VERIFY
  are manual for it; `confirm-modal.spec.ts` only exercises enrolment screens.
