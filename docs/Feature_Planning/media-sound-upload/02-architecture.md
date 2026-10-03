# Gift sound on Media — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

The feature stays in **Calendar / SecretGift** (it owns the gift, the
`gift_sound_path` column and the `canViewSound` rule). The bytes move to
**Media**'s private half, exactly as the gift image did. Media grows a raw
(non-image) private store and HTTP Range on `stream()`; it still authorizes
nothing. **Shared** loses `<x-shared::sound-upload>`, its Alpine code and its
lang file.

### 1.1 Changes in other domains

**Media** — direct API, no new extension point.

- New raw private store: `storePrivateFile()` (§3.1). Same scope rules as
  `storePrivate()` (`secret-gift/{id}` only), but bytes written as-is — no
  `ImageService`, no Intervention.
- `stream()` gains Range support for private paths (§3.1).
- GC: on the **private** disk, every file under a managed root is an original —
  private files never have variants — so the image-extension filter applies to
  the public disk only. An mp3 is then collected with no extension list. The
  zero-claim guard at the private root is unchanged.
- New Blade component `<x-media::sound-field>` (§4), replacing the Shared one.

**Calendar / SecretGift**

- Sound storage, serving and download go through `MediaPublicApi`; no direct
  `Storage::disk` use outside the legacy mover (§2.3).
- `SecretGiftMediaUsageProvider` claims `gift_sound_path` as well as
  `gift_image_path`.
- Nothing deletes sound files any more — replace/remove only rewrite/null the
  column; GC reclaims after 7 days. Shuffle and activity deletion need no
  change: dropping the rows drops the claims.

**Shared** — `sound-upload` component, its lang file and its test go.

## 2. Data model

### 2.1 Tables

No schema change. `calendar_secret_gift_assignments.gift_sound_path` (string,
nullable) keeps its meaning — a path — but its values move from the `local`
disk (`calendar/secret-gift/{activity_id}/sound-{giver}-{ts}.mp3`) to Media's
`private` disk (`secret-gift/{activity_id}/{hash}.mp3`).

### 2.2 Model

`SecretGiftAssignment` unchanged.

### 2.3 Lifecycle rules

| Event | Effect |
|-------|--------|
| Replace | column points at the new private file; old file unclaimed → GC after 7 days |
| Remove | column nulled; file unclaimed → GC after 7 days |
| Re-shuffle / activity deleted | rows deleted → claims gone → GC after 7 days |

**Legacy migration.** A data-only migration, mirroring
`2026_07_31_143000_move_secret_gift_images_to_media_private`: an idempotent,
reversible mover (`LegacyGiftSoundMover`, sibling of `LegacyGiftImageMover`)
copies each `local` file to `private:secret-gift/{activity_id}/{basename}`,
rewrites the column and deletes the source. Rows already on the target prefix
count as `already_migrated`; missing sources are reported (`missing`), not
fatal. `down()` moves them back. The mover keeps the basename (no rehash) so
the migration is a pure move. Like the image mover, it uses `Storage::disk`
directly — the accepted exception to "Calendar never touches disks".

## 3. PHP architecture

### 3.1 Public API

`MediaPublicApi`:

```php
// New. Raw private store for non-image files. Throws on a non-private scope.
// Path: {folderFor($scope)}/{hashName stem}.{guessed extension}
public function storePrivateFile(string $scope, UploadedFile $file): string

// Widened return type. Private paths: BinaryFileResponse (local driver),
// which answers Range with 206 / Content-Range / Accept-Ranges natively once
// prepared by the router. Public paths: unchanged behaviour.
// $headers still override (Content-Type, Content-Disposition, Cache-Control).
public function stream(string $path, array $headers = []): \Symfony\Component\HttpFoundation\Response
```

`exists()` unchanged. No delete method is added — deletion stays GC-only.

The extension is taken from the file's guessed MIME (`hashName()`), not the
client name, so a renamed file cannot pick its own extension. Validation
(`mimes:mp3`, 10 MB) stays in SecretGift's form request — Media validates
nothing about content, as with images.

### 3.2 Services

- `MediaService::storePrivateFile` — scope check, `putFileAs` on `private`.
- `MediaService::stream` — for a private path, build a `BinaryFileResponse`
  from the disk's absolute path, apply `$headers`; public path unchanged.
- `MediaService::gc` — private disk: all files are originals (§1.1).
- `SecretGiftService::saveGiftSound` stores via `storePrivateFile` and writes
  the path; `removeGiftSound` only nulls the column. Neither deletes.

### 3.3 Policy / authorization

Unchanged: `SecretGiftService::canViewSound` (giver always; recipient when
ENDED/ARCHIVED) checked in the controller before `stream()`. Media authorizes
nothing. Upload/replace/remove guarded by the existing ACTIVE-state check and
`SaveGiftRequest`. As with images (A18), no client-supplied path is ever
adopted — the form only carries a file and a remove flag.

### 3.4 Events and listeners

None.

### 3.5 Routes, controllers, form requests

Routes unchanged (`secret-gift.sound`, `secret-gift.download-sound`, GET).
Both actions become: `canViewSound` → 403; null column or `!exists()` → 404;
`stream($path, $headers)`.

- play: `Content-Type: audio/mpeg`, `Cache-Control: private, max-age=3600`,
  inline.
- download: `Content-Disposition: attachment; filename="gift-audio-{giver}-{id}.mp3"`
  (same name as today).

The hand-written Range code in `streamSound` is deleted. `SaveGiftRequest`
unchanged (`gift_sound`, `gift_sound_remove`).

## 4. Frontend architecture

`<x-media::sound-field>` in `Media/Private/Resources/views/components/`, a
move of the Shared component with its behaviour unchanged (assumption #1:
drag & drop, preview player, current sound playable, remove control).

- Props: `name`, `id`, `previewUrl` (route of the current sound, nullable),
  `maxSize`, `accept`, `removable`, `label`, `helpText`. Dropped: `disk`,
  `currentPath` and the `asset('storage/…')` fallback — the component never
  builds a URL itself, matching `image-field`'s private mode.
- Form contract unchanged: file input `{name}`, hidden `{name}_remove`. Not
  aligned on `image-field`'s `{name}[path]/[file]` shape — that shape exists
  for the reuse picker, which sound does not have.
- Alpine `soundUpload` moves with it (renamed `mediaSoundField`, same
  `@once` inline push as today).
- Lang: the used keys of Shared's `sound-upload.php` move to Media's
  `sound-field.php`; the three unused keys are dropped.

## 5. Deptrac

**No new edge.** `CalendarPrivate → MediaPublic` already exists; Media's
component is in `MediaPrivate` like `image-field`. Shared loses a component,
which removes no edge anyone else relies on.

## 6. Testing strategy

Integration (feature) tests, default level:

- **Media** — `storePrivateFile` stores raw bytes on `private` with the guessed
  extension and refuses a public scope; `stream()` of a private file answers a
  `Range: bytes=…` request with 206 + `Content-Range`, a plain request with 200
  + `Accept-Ranges: bytes`, and honours a supplied `Content-Disposition`; GC
  collects an unclaimed old mp3 on `private`, keeps a claimed one, keeps a
  young one, and still skips the root when nothing is claimed.
- **SecretGift** — upload/replace/remove write the column, never delete a file;
  provider claims sound paths; play/download keep their 403/404 rules, Range
  works through the route, download file name unchanged.
- **Mover** — moved / already_migrated / missing counts; `down()` round-trip.
- **Component** — `<x-media::sound-field>` renders the current sound from
  `previewUrl`, the file input and the remove flag; the Shared test goes.

Visual (VERIFY): drag & drop + preview player, seeking in the reveal player,
download.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | How Media stores a non-image private file | **A** new `storePrivateFile()` raw store, GC treats all private files as originals · **B** reuse `storePrivate()` (works only because `widths=[]` skips Intervention), GC learns `mp3` | A | An image method silently accepting audio is an accident, not a contract; GC needs no extension list. B was worth it only if one entry point for many file types mattered |
| 2 | Where HTTP Range lives | **A** inside `stream()` via `BinaryFileResponse` for private paths · **B** a separate `streamRanged()` · **C** keep Range code in SecretGift | A | Symfony handles Range natively; SecretGift's hand-written code is deleted; images get seeking for free. C would need Calendar to reach a disk. Holds while the private disk is `local` |
| 3 | Home and shape of the replacement widget (decided in DESIGN, not arbitrated) | `<x-media::sound-field>` · generic `<x-media::file-field>` · inside SecretGift | `<x-media::sound-field>` | Only consumer is sound; generic is speculative; collapsing widgets into SecretGift was tried and reverted for images |

## 8. File layout

```
app/Domains/Media/
  Private/Resources/views/components/sound-field.blade.php
  Private/Resources/lang/fr/sound-field.php
  Tests/Feature/MediaSoundFieldComponentTest.php
app/Domains/Calendar/Private/Activities/SecretGift/
  Support/LegacyGiftSoundMover.php
  Database/Migrations/YYYY_MM_DD_HHiiss_move_secret_gift_sounds_to_media_private.php
```

## 9. Risks acknowledged

- **`BinaryFileResponse` needs a local path.** If the private disk ever moves
  to S3 or similar, `stream()` loses Range. Revisit then (signed temporary
  URLs or a ranged read stream).
- **GC treating every private file as an original** assumes nothing ever
  writes variants or sidecar files under a private root. True today (private
  images have no variants by decision); revisit if private variants are ever
  introduced.
- **Range now also applies to gift images.** Harmless, but it is a behaviour
  change on the image endpoint; covered by the existing image tests.
- **Pre-deploy row count** for the sound migration, like O7 of the image
  migration, is not checked in BUILD — the `missing` count in the log is the
  signal.
