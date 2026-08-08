# News comment form retains text after submit

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-08 · **Domain(s):** `Comment`

## What it does

After a successful comment create, the matching localStorage draft is cleared
and the compose form does not restore that body into Quill. News made the bug
visible because it keeps the root form open (unlimited roots); chapters hide
the root form after one root and masked the same race. Mid-compose drafts still
restore when nothing was consumed.

## Key behaviour

- Success flash `comment.draft_consumed` → `window.__commentDraftConsumed` set
  inline, before the Vite draft module runs.
- Draft bootstrap clears the matching slot and skips restore for that scope.
- Unfinished drafts without a marker still restore; validation-failure recovery
  via submit flush is unchanged.
- No News policy change (still unlimited roots).

## Where the code lives

| Concern | Path |
|---------|------|
| Flash | `CommentController::store` → `comment.draft_consumed` |
| Marker | `comment-list.blade.php` → `window.__commentDraftConsumed` |
| Draft JS | `Comment/Resources/js/comment-draft/index.js` |
| Vitest | `Comment/Resources/js/comment-draft/index.test.js` |
| E2E | `e2e/tests/core/comment-draft-consume.spec.ts` |

## Extension points used

None new.

## Decisions worth remembering

- Fix in Comment draft ordering, not by capping news roots or dropping submit flush.
- Roots and replies share the same consume rule.
- Feature e2e promoted to core: shared Comment client path, invisible to PHP tests.

## Not done

- Deliberate non-goals: news one-root policy, draft UX redesign, chapter form gating.
- No leftovers pushed to the backlog.
