# Upload-widget strings — give them an owner

## What I want

Shared still ships `Resources/lang/fr/image-upload.php` and
`sound-upload.php` for components it no longer owns. Decide who owns those
strings and move them, or state once and for all that Shared keeps them.

## Why

Leftover from `shared-image-upload-cleanup/` (assumptions A5, A7): the widgets
moved into the SecretGift plugin but kept resolving `shared::image-upload.*` /
`shared::sound-upload.*`, because Story's `cover-tab-custom.blade.php` borrows
three `image-upload` keys (`drop_or_click`, `max_size`, `size_error`) without ever
rendering the widget. So the lang files have two unrelated consumers in two
domains and a name that matches neither.

Likely shape: SecretGift owns the widget chrome in its own lang file, Story owns
its three cover-tab strings, and Shared's two files disappear.

## Constraints or ideas I already have

- Pure string ownership: no UI copy change, no behaviour change.
- Touching Story's cover tab is a real (small) Story change — the previous task
  refused it on scope grounds, this one must accept it or drop the idea.

## Explicitly out of scope

- The upload widgets' markup or Alpine behaviour.
- Putting gift assets through Media.
