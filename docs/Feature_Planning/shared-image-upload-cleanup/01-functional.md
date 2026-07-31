# `<x-shared::image-upload>` cleanup — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

`<x-shared::image-upload>` has a single runtime consumer: SecretGift gift
preparation. This task removes that Shared orphan by collapsing the component
into the SecretGift activity plugin, without changing gift upload, preview,
removal, or private serving behaviour. The Shared lang file stays — Story's
cover tab borrows three of its strings.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Gift image | Image privée jointe à une assignation Secret Gift (`gift_image_path`), stockée hors disque public et servie sous contrôle d'accès |
| Shared image-upload | Ancien composant Blade générique d'upload d'image (`<x-shared::image-upload>`) |
| SecretGift image-upload | Même widget, désormais propriété du plugin Secret Gift |

No new user-facing nouns; UI copy is unchanged.

## 3. Roles & visibility

Unchanged from today's SecretGift rules. This cleanup does not alter who can
upload, see, or download a gift image.

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Nothing (auth-gated) | — |
| `user` / `user-confirmed` as giver | Own gift image while preparing | Upload / replace / remove via gift form |
| Recipient | Gift image only when activity is ENDED/ARCHIVED | View / download |
| Outsider | Nothing | — |
| Moderator / Admin | No new override in this task | — |

## 4. Functional requirements

### 4.1 Collapse ownership (no behaviour change)

1. SecretGift's gift-preparation form continues to offer the same image upload
   widget (drag/drop, preview, client size check, remove marker).
2. The widget is no longer a Shared component; Shared must not expose
   `<x-shared::image-upload>` after this task.
3. Server-side gift image rules stay as today: private `local` disk, path on
   the assignment, auth-gated serve with giver/recipient timing, immediate
   file delete on remove/replace.
4. Story's cover tab keeps working with the same three Shared lang keys
   (`drop_or_click`, `max_size`, `size_error`) — no Story UI change.

### 4.2 Sibling orphan (assumption)

`<x-shared::sound-upload>` has the same sole consumer (SecretGift preparation).
It is collapsed into SecretGift in the same pass so Shared does not keep a
second one-consumer upload widget. Behaviour of gift sound upload is unchanged.

### 4.3 Explicit non-changes

- No migration of gift images onto Media path/scope/GC/variants.
- No change to shuffle orphan-file behaviour (known gap, out of scope).
- No new tests required beyond what keeps existing SecretGift feature tests green
  after the Blade rename; no new E2E for the widget.

## 5. Lifecycle

Unchanged. Gift image files follow SecretGift's existing lifecycle (explicit
remove/replace; assignment rows on shuffle). This task does not add file GC for
shuffle orphans.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — no change; existing SecretGift auth/verified routes |
| Visibility / privacy | Unchanged — private disk + route-gated serve |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A — ownership move only |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | Unchanged; shuffle orphans remain a known non-goal |
| Media | **Do not** put gift images through Media — private disk, no variants, auth-gated URLs, immediate delete |
| Search | N/A |
| i18n | Shared `image-upload` lang file **stays** for Story; SecretGift may keep using those keys or its own for widget chrome — Story's three keys must keep resolving |
| Mobile | Unchanged widget behaviour |
| Accessibility | Unchanged |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Fate of `<x-shared::image-upload>`? | Collapse into SecretGift; delete from Shared |
| 2 | Move SecretGift images to Media? | No |
| 3 | Shared `image-upload` lang file? | Keep in Shared (Story borrows three keys) |
| 4 | Also collapse `<x-shared::sound-upload>`? | Yes (same sole consumer) |

(User confirmed the request constraints; #1/#4 are auto-mode assumptions — see
`DECISIONS.md`.)

## 8. Out of scope

- Reworking Media for private-disk gifts (API, GC, variants).
- Story cover upload (already on Media).
- Deleting or relocating the Shared `image-upload` lang file.
- Fixing SecretGift shuffle orphan files on disk.
- Changing gift visibility rules, validation limits, or serve routes.
- Collapsing any other Shared components beyond image-upload and sound-upload.

## 9. Open questions

None blocking. Non-blocking: whether SecretGift should eventually own copies of
the three Story-borrowed strings so Shared lang can shrink — deferred; Story
borrow stays as today.
