# Extend fanart Discord endpoint payload — architecture

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Fanart domain only: the hand-built payload in `FanartService::discordPending`. Documentation in `docs/Discord_Api_Usage.md` (Fanarts feed section).

## 2. Data model

None.

## 3. PHP architecture

- Fanartist and authors are resolved through the Shared `ProfilePublicApi::getPublicProfiles`, in **one** batch with the author ids from `StoryPublicApi::getAuthorIdsByStoryIds` merged with the fanartist ids. No per-item query.
- `chapterSlug` comes from the `StoryChapterDto` already fetched for `chapterTitle`.
- `description` is the stored sanitized HTML reduced to plain text; key omitted when empty.
- The `artist.id` is the user id (`ProfileDto::user_id`); `artist.avatarUrl` is `ProfileDto::avatar_url`.

## 4. Frontend architecture

N/A.

## 5. Deptrac

No new edge (Shared and StoryPublic already allowed for FanartPrivate).

## 6. Testing strategy

Extend `DiscordFeedTest`: artist block, multiple authors (co-author), chapterSlug present/absent, description present/absent, no extra queries growth is not asserted.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Shape | flat keys; nested `artist` / `authors` | nested | groups related data, additive for the bot |
| 2 | Description format | sanitized HTML; plain text | plain text | Discord markdown cannot render HTML |
