# Readlist module

This domain lets authenticated users bookmark stories to read later ("Pile à Lire"). Read lists are private to each user.

## What it provides
- **Add/Remove** button on story pages (hidden for the story author).
- **Counter badge** on story pages: number of users who added the story.
- **Read List page** (auth-only): cards with cover, title, authors, summary, genres, last update, trigger warnings, progress, and a "Keep Reading" button to the first unread chapter.
- **Progress**: computed from chapters read vs. total; first unread chapter derived automatically.
- **Filtering**: hide up-to-date stories (default ON) and single-select genre. Lazy loading (10 per page).

## UI components (to include in Story views)
- `<x-read-list::counter-badge :story-id="$story->id" />`
- `<x-read-list::add-button :story-id="$story->id" :story="$story" />`

## Route (auth)
- `GET /read-list` – list page with infinite scroll and inline expandable (truncated) chapter list.

## Notifications
For readers with a story in their list: new chapter, chapter unpublished/deleted, story visibility changes, story deleted. Authors can be notified when a user adds their story.

## Public API (for other domains)
`ReadListPublicApi` exposes:
- `getReadListCountForStory(int $storyId): int`
- `hasStoryInReadList(int $userId, int $storyId): bool`
- `addToReadList(int $userId, int $storyId): bool`
- `removeFromReadList(int $userId, int $storyId): void`
- `getReadListStoryIds(int $userId): array`

For list rendering, use `StoryPublicApi::getStoriesForReadList()` to fetch enriched story DTOs (authors, genres, progress, chapters, visibility) with filtering/pagination.

## Notes
- Private stories are hidden from the user’s list by the Story API; deleted stories are removed.
- Default sort: most recently updated first. Manual reordering not supported.
- Read lists are not shareable/public.