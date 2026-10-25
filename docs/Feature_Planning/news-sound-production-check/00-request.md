# News/static pages sounds — verify in production — request

## What I want

Check, on the production VPS, the two things the dev environment could not prove for sounds in news articles and static pages:

1. HTTP `Range` / seeking on public mp3 files served by nginx from `storage/` (the PHP dev server has no Range support, so seeking was never asserted).
2. Upload size limits: a news or static-page save carrying several 10 MB sounds (nginx `client_max_body_size`, PHP `post_max_size` / `upload_max_filesize`) — look for a 413 or a silently dropped upload. Dev `post_max_size` is 100M.

If a limit is too low, raise it or lower the per-sound cap / document the cap for editors.

## Why

The feature shipped with both unverified; a failure would only show in production.

## Constraints or ideas I already have

No code expected unless a check fails. Needs someone with production access.

## Explicitly out of scope

Private sounds (Secret Gift, Fanart) — already covered by Media's private half.
