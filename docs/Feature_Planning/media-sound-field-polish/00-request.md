# Media — sound field polish — request

*Leftover pushed back by `media-sound-upload/` WRAP (pre-existing defects seen
at checkpoint 1v and VERIFY, carried over unchanged when the widget moved from
Shared to Media).*

## What I want

In `<x-media::sound-field>`
(`app/Domains/Media/Private/Resources/views/components/sound-field.blade.php`):

1. The drop-zone icon `upload_audio_file` is not a Material Symbols Outlined
   glyph (the full font is loaded from Google Fonts in Shared's `head.blade.php`),
   so it renders as the literal text "upload _ audio_file". Use a name that
   exists (e.g. `upload_file` or `audio_file`).
2. An already-saved sound shows an empty file name and size under the player
   (`fileName` / `fileSize` start empty; only a newly picked file fills them).
   Show something meaningful (e.g. a generic label), or hide the row.

## Why

Visible glitches on the Secret Gift preparation form.

## Constraints or ideas I already have

- The component never builds a URL and knows nothing of the consumer; any name
  for a saved sound must come from a prop, not from the path.

## Explicitly out of scope

- Changing upload rules (mp3, 10 Mo) or the form contract.
