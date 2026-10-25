# Extend fanart Discord endpoint payload

**Status:** DONE — 2026-10-25 · **Domain(s):** `Fanart` · **Mode:** auto

## What it does

Each item of `GET /api/discord/fanarts/pending` (Discord API token) now also
carries the fanartist's `artist` block, every story author in `authors`, the
`chapterSlug` for chapter fanarts and a plain-text `description`. Additive only:
`id`, `title`, `artistName`, `storyTitle`, `storyUrl`, `chapterTitle`, `imageUrl`
keep their meaning. Eligibility, signed image URL and `mark-sent` are unchanged.
`title` was already always present (required at submission), so nothing was
added for it.

## Key behaviour

- `artist`: `{id, slug, avatarUrl}`. `id` is the **user id** (`ProfileDto::user_id`),
  `avatarUrl` is `ProfileDto::avatar_url`. `artist` is `null` (key kept) when the
  fanartist has no public profile; `artistName` is then `''`, as before.
- `authors`: `[{id, name, slug}]` for authors and co-authors
  (`StoryPublicApi::getAuthorIdsByStoryIds`), `name` = display name. Ids with no
  profile are skipped; `[]` when none.
- `chapterSlug`: set in the same branch as `chapterTitle`. Both are present or
  both are absent.
- `description`: the stored sanitized HTML reduced to plain text. `</p>` and
  `<br>` become `\n`, then `strip_tags`, `html_entity_decode`, `trim`. The key is
  omitted when the result is empty or the column is null.
- One `getPublicProfiles` call per request serves both the fanartists and the
  authors. This is checked by reading the code; no test asserts the query count.
- Read at poll time, nothing is stored. Items whose story is missing are still
  skipped.

## Where the code lives

| Concern | Path |
|---------|------|
| Payload | `app/Domains/Fanart/Private/Services/FanartService.php` (`discordPending`, private `discordAuthors`, `plainText`; the old `artistNames` helper was removed) |
| API doc | `docs/Discord_Api_Usage.md`, « Fanarts feed » section (JSON example and key notes updated) |
| Tests | `app/Domains/Fanart/Tests/Feature/DiscordFeedTest.php` (4 new cases) |
| Test helpers | `app/Domains/Fanart/Tests/helpers.php` (`botGetPending`, `sharedImage`) |

No migration, no route change, no new deptrac edge (Shared and StoryPublic were
already allowed for FanartPrivate).

## Extension points used

None.

## Decisions worth remembering

All of these were assumptions made without asking the user (auto mode). Each is
reversible.

1. « Profile ID » means the user id. The public contract has no separate profile id.
2. `description` is plain text, not HTML, because Discord cannot render HTML.
   Paragraphs are separated by newlines.
3. The keys are nested (`artist: {...}`, `authors: [...]`), not flat.
4. `artist` is `null` when the fanartist has no profile, rather than omitted, so
   the item shape stays stable for the bot.

## Plan vs code

- The plan's open items said the file-local helpers in `DiscordFeedTest.php`
  were out of scope and would not be moved. In fact they were moved to
  `Tests/helpers.php` in their own commit (`23defc5c`), before the feature
  commit. No Pest helper is now declared in the test file.
- Otherwise the code matches Phase 1 of the plan.

## Verification

VERIFY was skipped on purpose: the change is API-only and the Pest tests cover
it (artist block, co-authors, chapterSlug present/absent, description
present/absent). The manual API checks in the plan (a real `curl` against the
local endpoint) were not run.

## Not done

- **The Discord bot (separate repo) must be updated to consume the new keys.**
  Until then they are ignored and nothing breaks.
- Out of scope by design: bot changes, the signed image URL, eligibility rules,
  any UI.
- No e2e spec was written, so there is nothing to retire.
- No backlog rows were created.
