# `<x-shared::image-upload>` cleanup — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Calendar** owns the change, inside the existing **SecretGift** activity
plugin (`Private/Activities/SecretGift/`). The upload widgets are UI owned by
the only consumer; they are not a cross-domain Shared utility anymore.

**Shared** loses the two anonymous Blade components (`image-upload`,
`sound-upload`) and keeps `Resources/lang/fr/image-upload.php` unchanged.

**Media** is untouched. **Story** is untouched (continues to resolve
`shared::image-upload.*` for three cover-tab strings).

### 1.1 Changes in other domains

| Domain | Change | Kind |
|--------|--------|------|
| Shared | Remove the two anonymous components; lang file stays | deletion only |
| Calendar (SecretGift) | Host the two components under the plugin; point the preparation partial at them | ownership move |
| Story / Media | None | — |

No new public API, registry, or extension point.

## 2. Data model

No schema change. `gift_image_path` / gift sound storage, disks, and serve
routes stay as today.

### 2.1 Tables

None.

### 2.2 Model

None.

### 2.3 Lifecycle rules

Unchanged from SecretGift today (explicit remove/replace; no shuffle file GC).

## 3. PHP architecture

### 3.1 Public API

None. No other domain may call these widgets.

### 3.2 Services

`SecretGiftService` upload/remove/serve paths unchanged. Controllers unchanged.

### 3.3 Policy / authorization

Unchanged (`canViewImage`, activity ACTIVE for save, existing middleware).

### 3.4 Events and listeners

None.

### 3.5 Routes, controllers, form requests

None added or renamed. Existing `SaveGiftRequest` validation stays.

## 4. Frontend architecture

- Relocate the two anonymous Blade components (and their inline Alpine
  `alpine:init` registration) from Shared into
  `SecretGift/Resources/views/components/`.
- Register an anonymous component path for the SecretGift view namespace (same
  pattern Shared uses for `shared::`), so the preparation partial can call
  `<x-secret-gift::image-upload>` / `<x-secret-gift::sound-upload>` with the
  **same props** as today.
- Keep using `shared::image-upload.*` (and whatever lang keys sound-upload
  already uses) for widget chrome — no string duplication into Calendar lang
  in this task.
- No new Vite entry, no extracted JS module: Alpine stays inline in the Blade
  `@push('scripts')` block as today.

## 5. Deptrac

**No new edges.** Calendar already may depend on Shared (lang keys, layout).
No Calendar→Media edge is introduced.

## 6. Testing strategy

| Level | What |
|-------|------|
| Feature (existing) | `SaveGiftTest`, `ServeFileTest` — must stay green after the Blade rename; acceptance bar for “no behaviour change” |
| Component unit | Not added — pure UI relocate |
| Vitest | N/A |
| VERIFY | Smoke that gift preparation still shows the upload widget; no new Playwright suite required (A4) |

Optional small addition if BUILD wants a regression guard: assert the Shared
anonymous component path no longer resolves `image-upload` / `sound-upload`
(or that the Shared component files are gone). Prefer a filesystem / provider
assertion over a full HTTP round-trip.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where do the widgets live? | (a) collapse into SecretGift; (b) keep in Shared as exception; (c) move into Media | **(a)** | Sole consumer; Media has no private-disk/auth-URL model; Shared should not host one-consumer widgets |
| 2 | Gift storage via Media? | (a) no; (b) extend Media for private disk | **(a)** | Spec forbids inventing Media semantics; reverse cost of (b) is a Media redesign |
| 3 | Collapse sound-upload too? | (a) yes same pass; (b) image only | **(a)** | Same orphan shape, same form; leaving it recreates the leftover |
| 4 | Lang ownership | (a) keep Shared lang, Story + widgets keep keys; (b) move Story's three keys into Story now | **(a)** | Request constraint; (b) is a Story drive-by |
| 5 | Component registration | (a) anonymous path under `secret-gift::`; (b) class components | **(a)** | Matches current Shared shape; minimal diff |

## 8. File layout

New (moved) under SecretGift:

```
app/Domains/Calendar/Private/Activities/SecretGift/
  Resources/views/components/
    image-upload.blade.php
    sound-upload.blade.php
```

Registration lives in the existing `SecretGiftServiceProvider` boot path.
Deleted counterparts under Shared's `Resources/views/components/`.
No new PHP classes.

## 9. Risks acknowledged

| Risk | Trigger to revisit |
|------|--------------------|
| Shared still carries lang for a gone component | Story (or SecretGift) owns the three strings and Shared lang can shrink |
| `<x-shared::sound-upload>` lang/keys if any live only next to the component | Confirm during BUILD that sound strings are not orphaned in Shared |
| Inline Alpine name collision if both widgets register the same helper name on a page | Unlikely today (same page already loads both); keep distinct Alpine component names as in current Shared files |
