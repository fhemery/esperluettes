# Notification — load-more through fetchFragment — request

## What I want

Leftover from [`shared-fetch-fragment-adoption`](../_done/shared-fetch-fragment-adoption.md).
The Notification page's load-more (`Notification/.../pages/index.blade.php`)
still uses a raw `fetch` and injects whatever comes back: a user whose CGU were
reset, or whose session expired, gets a whole page injected in the list. It was
left out because it reads the `X-Has-More` response header, which
`window.fetchFragment` (text only) does not expose.

Decide at REFINE: either extend `fetchFragment` to expose headers (e.g. an
opt-in option returning `{ html, headers }`), or move the "has more" flag into
the fragment markup (data attribute) so the helper can be used unchanged.

## Why

Same failure mode the four adopted loaders had.

## Explicitly out of scope

Other raw `fetch` HTML loaders (Comment list, header search, report form) are
deliberate; see Shared's `AGENTS.md`.
