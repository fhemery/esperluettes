# `<x-shared::image-upload>` cleanup

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** The phase documents
> (`01`–`03`) remain as history; link to them from here when detail is needed.

**Status:** DONE — 2026-07-31 (VERIFY skipped) · **Domain(s):** `Calendar`
(SecretGift), `Shared` · **Spec:** [functional](./01-functional.md) ·
[architecture](./02-architecture.md) · [plan](./03-plan.md) ·
[decisions](./DECISIONS.md)

## What it does

Removes two Shared orphans. `<x-shared::image-upload>` and
`<x-shared::sound-upload>` were generic upload widgets, but
`media-consumer-migration/` left them with a single runtime consumer: the
SecretGift gift-preparation form. Both Blade files now live under the SecretGift
plugin and are called as `<x-secret-gift::image-upload>` /
`<x-secret-gift::sound-upload>`, with the same props, the same markup and the
same inline Alpine. Nothing about gift upload, replace, remove or auth-gated
serve changed — this is a pure ownership move.

## Key behaviour

- No behaviour change at all: no schema, route, validation, policy or visibility
  change. `SaveGiftTest` and `ServeFileTest` were the acceptance bar and are
  untouched.
- Shared no longer resolves `<x-shared::image-upload>` / `<x-shared::sound-upload>`;
  a Shared unit test fails if either file reappears under Shared.
- **The Shared lang files stayed.** `shared::image-upload.*` and
  `shared::sound-upload.*` are still in `Shared/Resources/lang/fr/`, and the
  relocated widgets still resolve them — no Calendar copies were made.
- Story's cover tab (`cover-tab-custom.blade.php`) borrows three
  `shared::image-upload` keys (`drop_or_click`, `max_size`, `size_error`) without
  ever using the component. That is why deleting the lang file is *not* a
  follow-on of deleting the component.
- Registration is `Blade::anonymousComponentPath(.../Resources/views/components,
  'secret-gift')` in `SecretGiftServiceProvider`, alongside the existing
  `componentNamespace` for class components. Side effect: every Blade file in
  that folder is now also a `<x-secret-gift::…>` tag, including the pre-existing
  `secret-gift.blade.php` (still rendered as a view by `SecretGiftComponent`).

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | none — no new API, registry or extension point |
| Service / policy | unchanged (`SecretGiftService::canViewImage()` / `canViewSound()`) |
| Controllers / routes | unchanged |
| Views / components | `app/Domains/Calendar/Private/Activities/SecretGift/Resources/views/components/{image,sound}-upload.blade.php` |
| Registration | `…/SecretGift/SecretGiftServiceProvider.php` (`anonymousComponentPath`) |
| Consumer | `…/SecretGift/Resources/views/partials/_gift-preparation.blade.php` |
| JS | none — Alpine stays inline in the widgets' `@push('scripts')` |
| Lang (borrowed) | `app/Domains/Shared/Resources/lang/fr/{image,sound}-upload.php` |
| Tests | `app/Domains/Shared/Tests/Unit/SharedUploadComponentsRemovedTest.php`; existing `app/Domains/Calendar/Tests/Feature/SecretGift/{SaveGiftTest,ServeFileTest}.php` |
| Migrations | none |

## Extension points used

None. No registry, no event, no notification, no Media usage provider. Deptrac
gained no edge — Calendar→Shared (lang) already existed, and no Calendar→Media
edge was introduced.

## Decisions worth remembering

- **Collapse, don't keep as a Shared exception** (tradeoff #1). A one-consumer
  widget in Shared invites the next domain to reuse semantics that are
  SecretGift's, not the app's.
- **Gift assets stay off Media** (tradeoff #2, assumption A2). They are private
  `local`-disk files, streamed through a controller, variant-less and never
  swept; Media has no private-disk / auth-URL model. Reversing this means
  designing that model first — the expensive one of the set.
- **Shared keeps the lang files** (tradeoff #4, A5/A7). Story borrows three keys
  without the component, so the strings outlive the widget either way; moving
  them is a Story change this task had no business making.
- **`sound-upload` came along in the same pass** (tradeoff #3, A3) — same orphan
  shape, same form, and leaving it would have recreated the leftover.
- **Anonymous component path, not class components** (tradeoff #5, A6) — mirrors
  the Shared shape and keeps the diff to one provider line.

## Not done

- **VERIFY was skipped at the user's request** — the visual QA checklist in
  [`03-plan.md`](./03-plan.md) is deliberately empty; the user smoke-checks gift
  preparation (image and sound modes, mobile) and the Story cover tab themselves.
  The gate was green, and the two SecretGift feature suites cover upload,
  replace, remove and auth-gated serve server-side.
- **Non-goals** (functional §8): no Media rework for private-disk gifts, no Story
  cover-upload change, no deletion/relocation of the Shared lang files, no fix
  for SecretGift shuffle orphan files, no other Shared component collapsed.
- **Shared lang ownership is still drifting.** Shared ships
  `image-upload.php` / `sound-upload.php` for components it no longer has. Pushed
  back to the backlog as `shared-upload-lang-ownership/`.
- **`ShuffleService` still orphans files on disk.** Re-running the shuffle deletes
  assignment rows in a transaction but never deletes the uploaded gift images and
  sounds under `calendar/secret-gift/{activity_id}/`. Pre-existing and explicitly
  out of scope here; recorded in the SecretGift README.
- Nothing was cut mid-build: the single planned phase shipped as designed, plus
  the optional regression guard the plan marked recommended.
- No e2e specs to retire — this task added none (A4), and
  `e2e/tests/features/` holds only its README.
