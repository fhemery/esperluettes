# Quotes — private stories

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-07-28 · **Domain(s):** `Quote`

## What it does

Lets confirmed readers who can **read** a story (including beta readers on
private stories) create quotes and notify authors. Citations tab entries from
private/community stories stay gated by the same Story access rules so strangers
never see those passages.

Depends on `story-author-check/` (`isAuthor` for the author block) — already
shipped on the same branch.

## Key behaviour

- `canQuote`: confirmed + not author + `filterUsersWithAccessToStory`.
- Non-owner Citations: omit rows the viewer cannot access (no placeholder).
- Owner always sees their full book; notes stay owner-only.
- Author notification fires on private/community create (unchanged listener).
- No moderator override to peek inaccessible private quotes.

## Where the code lives

| Concern | Path |
|---------|------|
| Policy | `app/Domains/Quote/Private/Services/QuotePolicy.php` |
| Profile filter | `QuoteService::filterVisibleForViewer` (unchanged) |
| Tests | `QuoteControllerTest`, `QuoteProfileControllerTest`, `QuoteLifecycleTest` |

## Extension points used

None new — reuses `StoryPublicApi::filterUsersWithAccessToStory` / `isAuthor`.

## Decisions worth remembering

- Access on create lives in `canQuote`, not a separate Gate in the form request.
- Profile filtering was already correct; this task pinned fellow-beta visibility.
- Do not revive `isAuthorOrCoAuthor`.

## Not done

- In-chapter author view (`quotes-author-view/`, now done).
- Quotes moderation (`quotes-moderation/`).
- Pagination/performance rewrite of load-all-then-filter.
