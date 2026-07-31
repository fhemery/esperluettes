# SecretGift gift images on Media (private) — request

## What I want

Migrate SecretGift gift **images** onto Media with a real private-disk model
(auth-gated serve, no public URLs). Use `<x-media::image-field>` with
`allowLibrary=false` and a consumer-supplied preview URL. Delete
`<x-shared::image-upload>` once SecretGift no longer uses it.

Text gifts already use `<x-editor::rich-text>` — leave as-is.
Sound stays on Shared for now; push a follow-up backlog row.

## Why

The first pass collapsed the Shared widget into SecretGift — wrong ownership.
Shared orphans should die by adopting Media (with privacy), not by hiding
under Calendar. Activity header images already use Media; gift images need the
private half of that story.

## Constraints or ideas I already have

- Privacy matters: gifts must not be world-readable via `/storage/…`.
- Add `allowLibrary` (default true) to `<x-media::image-field>`.
- Shared `image-upload` **lang** stays (Story cover borrows three keys).
- First collapse into SecretGift was reverted; do not revive that approach.

## Explicitly out of scope

- Sound upload → Media (backlog follow-up).
- Changing gift visibility rules (giver / recipient × activity state).
- Story cover upload UI (already Media-backed; only lang borrow).
- Reworking public Media scopes / activity header images (symlink env fix only).
