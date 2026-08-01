# Quote Domain

## Purpose

The Quote domain lets a reader save meaningful passages from chapters to a personal **quote book** ("Citations"). A quote is a personal keepsake — the reader collects passages that resonate with them and can attach a private note. It is deliberately **not** a feedback tool aimed at the author (that is what the future Annotations feature is for); the author only learns a passage was quoted through a notification.

The domain also carries the **in-chapter author view**: on their own chapter, an author (or co-author) can turn on a heat map of the passages readers quoted, see who quoted each one, and open a chapter summary listing every quoted passage with its count. **Notes are never part of it** — the aggregate payload has no note field at all.

Out of scope: moderation of quotes/notes, organising quotes into collections, and any social interaction on public quote books.

**Not done.** The note editor is a plain `<textarea>` rather than a rich-text editor, and the margin bookmark icon was never built. Moderation of quotes and notes is deferred; the reader's private note must never become visible to the author.

## Key concepts

- **Quote** — a verbatim plain-text passage saved from a chapter, always tied to one chapter. Duplicates (same passage, same user) are allowed; there is no dedup.
- **Note** — an optional, strictly private freeform text attached to a quote. It is visible **only** to the reader who wrote it — never to the author, other readers, moderators, or in notifications. The note is stored as sanitized HTML (bold / italic / custom-emoji only) even though the current input UI is a plain textarea.
- **Anchor** — how a quote is re-located in the chapter after the author edits it. Each quote stores three immutable plain-text strings: `highlighted_text` plus a short `prefix` and `suffix` (≤5 words each). Re-anchoring runs **client-side on every chapter view** using the shared anchoring JS; the server never re-computes anchors, it only stores them.
- **Stale quote** — a quote whose anchor can no longer be found in the current chapter body. In-chapter it simply stops being tinted; the quote row survives.
- **Quote book** — the reader's full collection, shown in a "Citations" tab on their profile. Visible to confirmed users by default; the owner can hide it from everyone but themselves via a user setting. Guests never see it.

## Who can quote

- Only **confirmed** users (`user-confirmed`) may create quotes, and only on chapters they can read.
- **Authors/co-authors cannot quote their own story** (story-level block, not just the chapter). They can quote other people's chapters.
- Guests and unconfirmed users cannot quote and never see a quote book (even a public one).

## Who can see the author view

- Only the story's **authors** (`role = 'author'`, so the author and any co-author), and only on their own chapters. Nobody else: not guests, not other readers, not beta readers, not moderators, not admins. The same `canViewChapterAggregate()` check gates the badge, the heat root and the endpoint, so they can never disagree.

## Architecture decisions

- **Standalone domain.** Quotes are a personal reading artefact with their own table, service, and public API. They are not comments (no comment dependency), not story content, and Profile only renders them — so none of those domains own the data.
- **No cross-domain foreign keys.** `chapter_id` and `story_id` are plain integers; `story_id` is denormalised onto the row so the quote book can show story title/authors without a join. Story/chapter references are resolved at render time through `StoryPublicApi`, and missing references surface as "chapter unavailable" rather than cascading deletes. `user_id` is nullable and nullified (not deleted) when the user is removed.
- **No local-storage drafts.** Unlike annotations, each quote is an immediate, independent AJAX call. The only client state is an Alpine store populated from the server on chapter open.
- **Shared anchoring, established here.** The three pure anchoring functions (`buildCanonicalText`, `extractAnchor`, `findAnchor`) live in `app/Domains/Shared/Resources/js/anchoring/`. Quote is the first consumer; the future Annotations feature reuses them from the same location. Full Vitest coverage lives beside them.
- **Own sanitizer profile.** Notes are cleaned through a dedicated `quote-note` HTMLPurifier profile (see `config/purifier.php`) rather than reaching into Comment's sanitizer, to avoid a cross-domain dependency.

## Front-end architecture

- The chapter reading page wraps the chapter body in the Comment domain's `<x-comment::annotable>` component and drops `<x-quote::toolbar-button>` into its `toolbar-actions` slot. Selecting text surfaces a generic floating toolbar (owned by Comment); clicking "Citer" opens the quote mini-form.
- On save, the client extracts the anchor, POSTs the quote, and adds it to the `quotes` Alpine store. A reactive effect re-tints matched passages (yellow) and a click on a tint opens a popover with the note plus edit/delete.
- The profile "Citations" tab is an Alpine component (`quoteList`) rendered by `<x-quote::profile-tab>`, hardcoded into Profile's `show.blade.php`. It renders the first page server-side (as JSON seed) and supports load-more, owner-only inline note editing, and deletion.
- The author view is client-aggregated: the badge's count is a server-side `COUNT`, but the rows are fetched on demand from `GET /quotes/chapter-aggregate?chapter_id=…` the first time the heat or the summary is opened, then grouped in the browser (`ui/author-summary.js`). Staleness is only knowable after re-anchoring, which is client-side by design — a server-computed grouping could not mark a stale passage. The heat toggle is **never persisted**: it is off on every page load.
- The JS bundle entry is `app/Domains/Quote/Resources/js/quote/index.js` (Vite), loaded by the toolbar button, mini-form, and profile tab components.

## Cross-domain delegation map

| Concern | Delegated to |
|---------|--------------|
| Story/chapter metadata, author IDs, story access checks, authorship (`isAuthor`) | `StoryPublicApi` |
| Author/quoter display names, avatars, profile slugs | `ProfilePublicApi` (Shared contract) |
| "Is the viewer confirmed?" role checks | `AuthPublicApi` |
| `hide-quotes-tab` preference storage + settings-page tab | `SettingsPublicApi` (registered under the Profile tab, Privacy section) |
| Author notification when a passage is quoted | `NotificationPublicApi` |
| Text anchoring / re-anchoring (pure JS) | `Shared/Resources/js/anchoring/` |
| Floating selection toolbar + `<x-comment::annotable>` host | Comment domain |
| Rendering the "Citations" profile tab | Profile domain (hardcoded component) |
