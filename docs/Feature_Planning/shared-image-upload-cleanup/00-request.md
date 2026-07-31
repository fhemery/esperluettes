# `<x-shared::image-upload>` cleanup — request

## What I want

`<x-shared::image-upload>` now has exactly one consumer: SecretGift
(private `local` disk, no Media path/scope/GC semantics). Decide its fate:
move SecretGift onto `<x-media::image-field>`, collapse the component into
SecretGift, or leave it in Shared as a deliberate exception.

## Why

`media-consumer-migration/` stripped the other consumers but left this as a
follow-up so the gift-preparation flow wasn't dragged into that task.

## Constraints or ideas I already have

- The `shared::image-upload` **lang file stays** — Story's cover tab borrows
  those strings without using the component. Do not delete it.
- SecretGift images are private-disk, route-served, variant-less, never swept.
  Media's model may not apply; don't force a move that invents fake Media
  semantics.

## Explicitly out of scope

- Reworking Media's public API / GC for private-disk gifts (unless we
  explicitly choose that path).
- Story cover upload (already on Media; only the lang strings matter here).
