# SecretGift gift images on Media (private) — architecture

> DESIGN output. Shape and contracts, not a change list.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Media** owns private-disk storage, streaming bytes, GC over private folders,
and `<x-media::image-field>` props (`allowLibrary`, `previewUrl`).

**Calendar / SecretGift** owns gift visibility (`canViewImage`), the
`secret-gift.image` route, assignment column `gift_image_path`, and a
`MediaUsageProvider` for those paths. It calls `MediaPublicApi` for store and
stream; it does not touch disks directly for images.

**Shared** loses `image-upload.blade.php` only. Lang + `sound-upload` stay.

**Editor / Story** untouched.

### 1.1 Changes in other domains

| Domain | Minimum change |
|--------|----------------|
| Media | `storePrivate`, `stream`, disk resolution, GC on `private` disk; image-field props |
| Calendar | SecretGift image save/serve via Media; usage provider; switch Blade to image-field; migrate existing files |
| Shared | Delete image-upload component |

## 2. Data model

No new tables. `calendar_secret_gift_assignments.gift_image_path` remains a
nullable path string; values become Media private paths
(`secret-gift/{activityId}/{hash}.ext`) instead of
`calendar/secret-gift/{activityId}/{giverId}.ext` on `local`.

### 2.3 Lifecycle rules

Clear path on remove/replace → GC after grace. No sync `Storage::delete` for
images. Sound lifecycle unchanged (sync on `local`).

## 3. PHP architecture

### 3.1 Public API (`MediaPublicApi`)

```php
storePrivate(string $scope, UploadedFile $file, array $widths = []): string
stream(string $path, ?int $width = null, array $headers = []): StreamedResponse
exists(string $path): bool
// diskForPath may stay internal to MediaService if stream/exists suffice
```

- `store()` remains public-disk only.
- `originalUrl` / `variantUrl` stay public-only; calling them on a private path
  is undefined / must throw — do not invent public URLs for private files.
- Default `$widths = []` → original only (assumption A3).

### 3.2 Services

`MediaService`: `PRIVATE_DISK = 'private'`; `folderFor` accepts
`secret-gift/{activityId}`; `gc()` sweeps private managed folders with the same
zero-claim safety guard as public.

### 3.3 Policy / authorization

Unchanged in SecretGift (`canViewImage`). Media `stream` performs **no** auth.

### 3.4 Events

None.

### 3.5 Routes / controllers / requests

- Keep `GET secret-gift.image` — after `canViewImage`, call `MediaPublicApi::stream`.
- `SaveGiftRequest` + `saveGift`: accept `gift_image.path` / `gift_image.file`
  like `ActivityController::resolveImage()`; drop `gift_image_remove`.
- Sound fields unchanged.

## 4. Frontend architecture

```blade
<x-media::image-field
    name="gift_image"
    :scope="'secret-gift/'.$activity->id"
    :path="$assignment->gift_image_path"
    :preview-url="…"   {{-- route('secret-gift.image', …) when path set --}}
    :allow-library="false"
    :show-alt="false"
    :show-caption="false"
    …
/>
```

- `allowLibrary` default `true` (no behaviour change for News/FAQ/Calendar admin).
- `previewUrl` when set overrides Media-built asset URLs for the current preview.

## 5. Deptrac

**No new edges.** `CalendarPrivate → MediaPublic` already exists.

## 6. Testing strategy

| Level | Coverage |
|-------|----------|
| Media feature/unit | `storePrivate` writes `private` disk; `stream` returns bytes; `originalUrl` rejects/does not invent public URL for private paths; GC claims/skips private folders |
| SecretGift feature | `SaveGiftTest` / `ServeFileTest` on `Storage::fake('private')`; provider claims paths |
| Shared unit | image-upload Blade gone; sound-upload still present |
| VERIFY | Manual smoke checklist (user may run) |

## 7. Tradeoffs locked

| # | Question | Options | Chosen | Why |
|---|----------|---------|--------|-----|
| 1 | Privacy | (a) Media private API (b) public disk (c) UI-only | **(a)** | User; gifts must stay confidential |
| 2 | Auth placement | (a) consumer route + Media stream (b) Media visibility registry (c) signed URLs | **(a)** | Matches Auth compliance PDFs; Media stays rule-free |
| 3 | Disk | (a) `private` (b) keep `local` | **(a)** | Existing Laravel disk, `serve => false` |
| 4 | Variants | (a) none (b) 400/800 | **(a)** | Matches current gift behaviour |
| 5 | Delete Shared image-upload | (a) yes after migrate (b) keep | **(a)** | Sole consumer gone |
| 6 | Sound | (a) this task (b) backlog | **(b)** | User |

## 8. File layout (new)

```
Media/Private/Services/MediaService.php          # PRIVATE_DISK, storePrivate, stream, gc
Media/Public/Api/MediaPublicApi.php              # expose methods
Media/.../components/image-field.blade.php       # allowLibrary, previewUrl

Calendar/.../SecretGift/Support/SecretGiftMediaUsageProvider.php
(+ artisan command or one-shot migrate for existing local files)
```

## 9. Risks acknowledged

| Risk | Trigger |
|------|---------|
| Existing `local` files missed by migration | Pre-deploy inventory; command must be idempotent |
| `originalUrl` accidentally used on private path | Test that public URL helpers refuse private paths |
| GC wipes private folder with missing provider | Existing zero-claim skip; provider registered in SecretGiftServiceProvider |
| Form shape change breaks clients | Only browser form; update tests to `gift_image[file]` |
