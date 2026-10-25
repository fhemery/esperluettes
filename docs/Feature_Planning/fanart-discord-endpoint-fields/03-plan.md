# Extend fanart Discord endpoint payload — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Feed payload additions + API doc | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes.

No refactor phase and no checkpoint: the change is purely additive to one
hand-built array, its only consumer is the external Discord bot, and the
existing `DiscordFeedTest` cases stay unchanged and green.

## Working agreement

- One phase = one commit. Keeps `pnpm run gate` green, revertable on its own.
- Failing test first, then the implementation.

---

## Phase 1 — Feed payload additions + API doc

**Goal.** Each item of `GET /api/discord/fanarts/pending` gains `artist`,
`authors`, `chapterSlug` (chapter fanarts only) and `description` (when
non-empty), with existing keys untouched (architecture §3, §7).

**Deliverables.**
- `app/Domains/Fanart/Private/Services/FanartService.php`, method
  `discordPending()`:
  - Fetch `$authorIds = $this->stories->getAuthorIdsByStoryIds($storyIds)`
    (`array<int storyId, int[] userIds>`, authors and co-authors).
  - Resolve **one** batch of profiles:
    `$this->profiles->getPublicProfiles(array_unique([...fanartist ids, ...all author ids]))`
    → `array<int userId, ProfileDto>` (`user_id`, `display_name`, `slug`,
    `avatar_url`). This replaces the call through `artistNames()`; delete the
    now-unused private `artistNames()` helper (keep `artistName` populated from
    the same profile map, `''` when missing, as today).
  - Per item add:
    - `'artist' => ['id' => $p->user_id, 'slug' => $p->slug, 'avatarUrl' => $p->avatar_url]`
      when the fanartist profile is found, else `'artist' => null`.
    - `'authors' => [ ['id', 'name', 'slug'], ... ]` from `$authorIds[$story_id]`,
      mapping each id through the profile map (`name` = `display_name`);
      ids with no profile are skipped; `[]` when none.
    - `'chapterSlug' => $chapters[$fanart->chapter_id]->slug` inside the existing
      `chapterTitle` branch (`StoryChapterDto::$slug` exists).
    - `'description'` = plain text of `$fanart->description` (stored sanitized
      HTML): replace `</p>` and `<br>` variants with `"\n"`, `strip_tags`,
      `html_entity_decode(..., ENT_QUOTES | ENT_HTML5, 'UTF-8')`, `trim`; key
      omitted when the result is `''` or the column is null.
- `docs/Discord_Api_Usage.md`, section « Fanarts feed »: add the new keys to the
  JSON example (`artist`, `authors` with two entries, `chapterSlug`,
  `description`) and one sentence each: `artist.id` is the user id, `artist` is
  `null` when the profile is unavailable, `authors` lists all authors and
  co-authors, `chapterSlug` is omitted with `chapterTitle`, `description` is
  plain text (paragraphs separated by newlines) omitted when empty.
- `app/Domains/Fanart/README.md`: no change expected (it describes the Discord
  relay at feature level, not the payload). Touch only if a sentence becomes
  false. `app/Domains/Fanart/AGENTS.md`: no Discord content, no change.

**Tests.** In `app/Domains/Fanart/Tests/Feature/DiscordFeedTest.php` (new
shared helpers, if any, go in `app/Domains/Fanart/Tests/helpers.php`, not in the
test file):
- `it('exposes the fanartist id, slug and avatar url')` — bob's fanart on alice's
  story: `data.0.artist.id === bob id`, `artist.slug` equals bob's profile slug,
  `artist.avatarUrl` is a non-empty string.
- `it('lists every author of the story, co-authors included')` — story by alice
  plus a co-author added with `addCollaborator($story->id, $coAuthor->id, 'author')`
  (Story `Tests/helpers.php`): `authors` has 2 entries with matching `id`,
  `name` (display name) and `slug`; order-independent assertion.
- `it('sends the chapter slug only for a chapter fanart')` — chapter fanart has
  `chapterSlug === $chapter->slug`; a story-level fanart has no `chapterSlug`
  key (`assertJsonMissingPath` or `array_key_exists` false).
- `it('sends the description as plain text and omits it when empty')` — a
  fanart with description `<p>Un <strong>beau</strong> dessin &amp; plus</p><p>Fin</p>`
  yields `"Un beau dessin & plus\nFin"`; a fanart with `description => null` has
  no `description` key.
- Existing tests unchanged and green (`artistName`, `chapterTitle`, `imageUrl`…).

**Acceptance.**
- ✅ A pending item carries `artist: {id, slug, avatarUrl}` of the fanartist.
- ✅ `authors` contains every author and co-author of the story as `{id, name, slug}`.
- ✅ `chapterSlug` present iff `chapterTitle` present.
- ✅ `description` is tag-free plain text, absent when the fanart has none.
- ✅ All pre-existing keys and `DiscordFeedTest` cases unchanged.
- ✅ Profiles are fetched in a single `getPublicProfiles` call per request (code
  review; not asserted by test, per architecture §6).
- ✅ `docs/Discord_Api_Usage.md` Fanarts feed example and notes list the new keys.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

No UI. VERIFY runs these as API/test checks.

| Surface | Check | OK? |
|---------|-------|-----|
| `DiscordFeedTest` | `./vendor/bin/sail artisan test --filter=DiscordFeedTest` all green, new cases included | |
| `GET /api/discord/fanarts/pending` (local, bot token) | item for a chapter fanart with description shows `artist`, `authors`, `chapterSlug`, plain-text `description` | |
| Same endpoint, story-level fanart without description | no `chapterSlug`, no `description`, `authors` still filled | |
| Same endpoint, story with co-author | both authors listed with id/name/slug | |
| Same endpoint, no token | still 401 | |
| `docs/Discord_Api_Usage.md` | example JSON matches a real response's keys | |

## Open items

- None blocking. Verified now: `ProfileDto` has `user_id`, `display_name`,
  `slug`, `avatar_url`; `StoryChapterDto` has `slug`;
  `StoryPublicApi::getAuthorIdsByStoryIds` exists and returns authors
  (co-authors are `author`-role collaborators); `addCollaborator()` exists in
  Story `Tests/helpers.php`.
- `artist: null` vs omitting the key when the fanartist has no profile is a
  plan-level choice (01 §4.2 says "omitted / null-safe"); null keeps the shape
  stable for the bot. Reversible. (Phase 1)
- `DiscordFeedTest.php` already declares file-local helpers (`botGetPending`,
  `sharedImage`), contrary to the AGENTS.md rule; out of scope here, not moved.
