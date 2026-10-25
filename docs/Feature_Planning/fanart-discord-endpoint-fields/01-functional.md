# Extend fanart Discord endpoint payload — functional specification

## 1. Overview

Each item of the Fanart Discord feed (`GET /api/discord/fanarts/pending`) carries more context so the Discord bot can link to and credit the right people and pages: the fanartist's profile, all authors of the story, the chapter slug and an optional description. Additive only: existing keys keep their meaning.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Fanartiste | the user who submitted the fanart (`fanarts.user_id`) |
| Auteurs | all authors and co-authors of the story the fanart targets |

## 3. Roles & visibility

Unchanged: the endpoint is guarded by the Discord API token and only lists fanarts already eligible for the feed (published images shared on Discord, story readable as guest). The new fields are public profile data.

## 4. Functional requirements

### 4.1 Payload additions per item
1. `artist`: `{ id, slug, avatarUrl }` of the fanartist (existing `artistName` stays).
2. `authors`: list of `{ id, name, slug }` for every author of the story.
3. `chapterSlug`: present only when the fanart targets a chapter (like `chapterTitle`).
4. `description`: plain text, present only when the fanart has a description.
5. `title` is already always present (required at submission): nothing to add.

### 4.2 Edge paths
- A fanartist or author without a retrievable profile is omitted / null-safe: the item is still sent.
- Items are still skipped when the story is missing (as today).

## 5. Lifecycle

Read at poll time, not stored. Deactivated users follow whatever the profile API already returns.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | unchanged (Discord API token) |
| Visibility / privacy | public profile data only; description is the already public fanart description |
| Settings / Notifications / Events / Statistics / Moderation / Media / Search / i18n / Mobile / Accessibility | N/A |
| Lifecycle / cascade | N/A |

## 7. Decisions confirmed

None from the user beyond the request.

## 8. Out of scope

- Changing the Discord bot, the signed image URL, or eligibility rules.
- Any UI.

## 9. Open questions

- « Profile ID » is read as the user id: no separate profile id exists in the public contract. Non-blocking.
