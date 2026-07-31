# Shared `image-upload` lang file without a component — request

*Leftover pushed back by `shared-image-upload-cleanup/` (assumption A6).*

## What I want

Shared ships `Resources/lang/*/image-upload.php` for a Blade component that no
longer exists. Story's `cover-tab-custom.blade.php` borrows three of its keys
(`drop_or_click`, `max_size`, `size_error`) for its own dropzone and never used
the widget. Move those keys where they are actually used and delete the Shared
file.

## Why

A lang namespace with no component is a trap: the next reader either
reintroduces an `image-upload` component in Shared (image handling belongs to
Media) or deletes the file and breaks the Story cover tab. Documented in
`app/Domains/Shared/AGENTS.md`, but a comment is not a fix.

## Constraints or ideas I already have

- Only Story borrows the keys — verify with
  `rg -n 'shared::image-upload' app/` before moving anything.
- The obvious destination is Story's own lang namespace. Consider instead
  whether the cover tab should use `<x-media::image-field>` outright, which
  would delete the borrow and the custom dropzone together.
- `TranslationKeysExistTest` asserts every static key resolves in `fr`, so the
  move is covered by the suite; the locale is `zz` in tests, so assert with
  `Lang::has()`, not `__()` output.
- Small and mechanical — `auto` mode.

## Explicitly out of scope

- `<x-shared::sound-upload>` and its lang file (`media-sound-upload/`).
- Redesigning the Story cover UI.
