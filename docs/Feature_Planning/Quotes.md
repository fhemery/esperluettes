# Quotes — Functional Specification

## 1. Overview

The Quotes feature lets a reader save meaningful passages from chapters to a personal **quote book**. Where Annotations are a feedback tool directed at the author, a Quote is a personal keepsake — the reader collects passages that resonate with them, optionally attaching a private note.

The reader:

- Highlights a passage and clicks **"Citation"** in the same floating toolbar as "Annoter".
- Optionally adds a short private note in a mini-form.
- Finds all their quotes in a dedicated **"Citations"** tab on their profile page.

The chapter author:

- Receives a notification when their work is quoted (v1).
- Eventually sees quoted passages highlighted in the chapter alongside a list of quoters (vNext — §4.5).

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| **Quote** | A passage of chapter text saved to a reader's quote book. Always tied to a specific chapter. |
| **Note** | A short optional freeform text the reader attaches to a quote. Strictly private — visible only to the reader, never to anyone else. |
| **Anchor** | The logical position of the quote inside the chapter content. Identical mechanism to Annotations: verbatim plain-text snippet plus a short plain-text prefix and suffix. Re-located client-side on each chapter view. |
| **Stale quote** | A quote whose anchor can no longer be located in the current chapter body (because the author edited or removed that passage). |
| **Quote book** | The reader's full personal collection of quotes, displayed in a dedicated profile tab ("Citations"). |

## 3. User Roles & Visibility

| Role | Can create quotes | Whose quote book they can see |
|------|-----------------|-------------------------------|
| **Guest** | No | None |
| **Unconfirmed user** | No | None |
| **Reader (confirmed, non-author)** | Yes, on chapters they are allowed to read | Their own (always). Other confirmed users' quote books only if the owner has made theirs public. |
| **Chapter author / co-author** | No — blocked on their own chapters. Yes on any other chapter they can read. | Same as Reader. |
| **Moderator** | Yes (same as Reader) | All public quote books. |

Notes:
- An author blocked from quoting their own chapter can still quote chapters from other stories.
- The author does **not** have special access to readers' quote books or notes. They learn about a quote only via the notification (§8).
- Private chapter access gates quote visibility: a confirmed user browsing a public quote book does **not** see quotes from chapters they cannot access (§4.3).

## 4. Functional Requirements

### 4.1 Creating a quote

#### Trigger

While reading a chapter, the reader highlights a passage. The same floating toolbar as Annotations appears. The toolbar contains, in this order:

1. **"Annoter"** — existing (opens the annotation inline form).
2. **"Citation"** — new (opens the quote mini-form, described below).
3. *[Quick emoji buttons — vNext, Annotations roadmap item A]*

#### Mini-form

Clicking **"Citation"** opens a small inline form just below the selection, containing:

- An **optional note field** using the same simplified rich-text editor as Annotations (bold, italic, custom emojis only). No minimum length — the note may be left completely empty.
- A **Save** button.
- A **Cancel** button.

Behavior:

- Saving (button or Ctrl/Cmd+Enter) immediately saves the quote **to the server via AJAX**. Unlike annotations, quotes do not go through a local-storage draft stage — each quote is a direct, independent server call, with no dependency on posting a root comment.
- On success: the mini-form closes; the highlighted passage gains a distinct background tint (§4.2).
- On failure (network, auth, validation): an inline error is displayed; the mini-form stays open so the reader can retry.
- Cancel: the mini-form closes; no quote is saved; the highlight is dropped.

#### Constraints

- The reader must be logged in and confirmed.
- Authors and co-authors of the chapter cannot quote it.
- An empty / whitespace-only selection does not show the toolbar (same rule as Annotations).
- Maximum highlighted text length: **500 plain-text characters**. Selections beyond the cap show a tooltip "Sélection trop longue" and disable the "Citation" button.
- A reader may quote the same passage multiple times (e.g., to attach different notes at different points in time). No deduplication.

### 4.2 Visualizing quotes while reading

After saving a quote:

- The highlighted passage gains a **distinct background tint** (yellow, in the style of Medium highlights), visually separate from the annotation tint used by the Annotations feature.
- On `md+` viewports: a small **bookmark icon** appears in the right margin at the line of the quoted passage (analogous to annotation avatars, but simpler — a single icon regardless of how many quotes the reader has on that line). This icon is visible only to the reader themselves.
- Clicking the tint or the margin icon opens a small popover showing:
  - The quoted text (truncated with ellipsis if long).
  - The reader's private note, if any. Clearly labelled as private.
  - **Edit note** action — opens the note field in-place.
  - **Delete quote** action — removes the quote immediately via AJAX.

When the reader returns to the chapter later, their quotes re-render from the server (no local storage involved).

On mobile (below `md`): no margin icon is rendered. The yellow tint still appears on the highlighted passage. Tap on the tint opens the popover anchored below the passage.

### 4.3 Quote book (profile tab)

A new **"Citations"** tab on the reader's profile page. Its visibility is controlled by a setting (§4.4).

The tab displays quotes sorted by date added, most recent first. Each entry shows:

- The quoted text as a plain-text blockquote.
- Story title (link to story page).
- Chapter title (link to chapter page, if the chapter is still accessible to the viewer).
- Author avatar(s) and display name(s).
- Date added.
- The private note — **shown only to the reader themselves**. When a confirmed user views a public quote book, the note column is completely absent from the layout; it is not shown as blank, it simply does not exist in the rendered output.

#### Stale and unavailable quotes

- **Stale quote** (passage edited or deleted from the chapter): the blockquote shows the original `highlighted_text` with a **"Passage plus présent dans le chapitre"** badge below it. All other metadata (story, chapter, authors) remains.
- **Unavailable chapter** (chapter deleted, unpublished, or access revoked): a **"Chapitre non disponible"** badge replaces the chapter link. The quoted text is still preserved and displayed.

Actions available in the quote book (reader's own tab only):

- **Edit note** — opens the note field inline, same simplified editor.
- **Delete quote** — immediate, removes the entry.

### 4.4 Quote book visibility & settings

Quote book visibility is **private by default**. The reader can change it to public in the Settings, under a new "Confidentialité" (or existing privacy section):

- **Privé (default)**: only the reader sees the "Citations" tab on their profile.
- **Public**: confirmed users can view the tab. Guests and unconfirmed users see nothing.

When set to public, **confirmed users who do not have access to a quoted private or restricted chapter do not see that quote entry at all** — the entry is simply not rendered for them. There is no placeholder.

Filter state for the tab (e.g., filter by story) is per-session and not persisted.

### 4.5 Author visibility in the chapter (vNext)

In a future version, the chapter page will show the author(s) which passages have been quoted, with an aggregate count and the list of readers who quoted. This follows a similar gutter pattern to the Annotations vNext roadmap. Not in scope for v1; no in-chapter UI for authors is built in v1.

### 4.6 Moderation

Out of scope for v1. The quoted text is verbatim chapter content (not user-generated); the note is private and not visible to moderators. If abuse patterns are identified (e.g., saving quotes from chapters to circumvent access controls), moderation can be added in a later version.

## 5. Anchoring & Re-anchoring

The anchoring mechanism is **identical to Annotations** (Chapter_Annotations.md §5). Each quote stores:

- `highlighted_text`: verbatim plain-text selection.
- `prefix`: up to 5 plain-text words before the selection.
- `suffix`: up to 5 plain-text words after the selection.

These three fields are immutable after creation. Re-anchoring runs client-side on each chapter view using the same `findAnchor` algorithm and the same canonical plain-text projection of the chapter body (HTML tags stripped, custom emoji blots replaced by `:name:`, block boundaries as a single space).

Display rules:

| Anchor status | In-chapter tint | In quote book |
|---------------|----------------|---------------|
| `ok` / `moved` | Shown (yellow tint) | Normal |
| `missing` | Not shown | Shown with "Passage plus présent" badge |

Quotes from chapters the viewer cannot access skip re-anchoring entirely (the quote entry is filtered before rendering).

## 6. Constraints

| Constraint | Value |
|-----------|-------|
| Note min length | 0 (optional) |
| Note max length (plain text) | 1000 characters |
| Highlighted text min length | 1 non-whitespace character |
| Highlighted text max length (plain text) | 500 characters |
| Editor formatting (note) | Bold, italic, custom emojis only |
| Anchor context size (each side) | Up to 5 words (same as Annotations) |
| Quotes per (user, chapter) | No hard cap |
| Duplicate quotes (same passage, same user) | Allowed |
| Save mechanism | Direct AJAX (no local-storage draft) |

## 7. Privacy Summary

| Viewer | What they see |
|--------|---------------|
| **Reader (quote author)** | Their own quote tab (always). Yellow tint + margin icon on quoted passages in the chapter. Private note in popover and in their quote book tab. |
| **Confirmed user** | A public quote book: quoted text + story/chapter/author info. Never the private note. Quotes from chapters they cannot access are hidden. |
| **Guest / unconfirmed user** | Nothing — no quote tab visible even if the quote book is public. |
| **Chapter author / co-author** | Notification content (reader name + highlighted passage). No note. No in-chapter visibility in v1. Cannot see the reader's quote book unless it is public (same rule as any confirmed user). |
| **Moderator** | All public quote books (no special override on private ones in v1). |

## 8. Notifications

When a reader saves a quote, the system:

1. Emits a domain event (e.g., `ChapterPassageQuoted`) carrying: quoter user ID, chapter ID, story ID, `highlighted_text`.
2. A notification listener converts the event into a `ChapterQuoteNotification` (new type) and dispatches it to each author and co-author of the chapter via the existing Notification domain.
3. The notification content shows: the reader's display name and avatar, the chapter title, and the quoted passage (plain text).
4. The note is **never included** in the notification.

No notification is emitted on:
- Note edits.
- Quote deletions.
- A quote going stale (passage edited out of the chapter).

## 9. Out of Scope (v1)

- **In-chapter author view** of quoted passages (vNext — §4.5).
- **Moderation** of quotes or notes (§4.6).
- **Organizing quotes** into collections, tags, or user-defined groups.
- **Social interactions** on public quote books (liking a quote, reacting, sharing a direct link to a single quote).
- **Filtering or sorting** the quote book beyond chronological order — v1 is date desc, full stop.
- **Cross-story / cross-chapter navigation** in the quote book (e.g., "all quotes from this story").
- **Quoting author notes or story descriptions** — chapter body only, same rule as Annotations.
- **Persistent filter / sort preferences** for the quote book tab.
- **Notifications on note edits or quote deletions.**
- **Moderator access to private quote books.**

## 10. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Toolbar placement | "Citation" is a button on the same floating toolbar as "Annoter", second position |
| 2 | Who can quote their own chapter | Authors and co-authors are blocked (same rule as Annotations) |
| 3 | Quote book default visibility | Private |
| 4 | Public visibility audience | Confirmed users only (guests and unconfirmed see nothing) |
| 5 | Private chapter in a public quote book | Entry completely hidden from users without chapter access (no placeholder) |
| 6 | Author in-chapter view | vNext — not v1 |
| 7 | Note visibility | Strictly private — never shown to anyone other than the reader, including in a public quote book |
| 8 | Note minimum length | 0 (optional field) |
| 9 | Stale quote display | "Passage plus présent dans le chapitre" badge; original `highlighted_text` preserved |
| 10 | Quote from deleted/unpublished chapter | Preserved in quote book with "Chapitre non disponible" badge |
| 11 | Save mechanism | Direct AJAX on Save click or Ctrl/Cmd+Enter; no local-storage draft |
| 12 | Duplicate quotes (same passage) | Allowed |
| 13 | Notifications in v1 | Yes — `ChapterPassageQuoted` domain event → `ChapterQuoteNotification` to all chapter authors/co-authors |
| 14 | Note included in notification | No |
| 15 | Anchor mechanism | Identical to Annotations (prefix + highlighted_text + suffix, plain text, client-side re-anchoring) |
| 16 | Quote book sort order | Date added, descending (most recent first) |

## 11. Open questions (none blocking v1)

- **vNext: in-chapter author view.** Exactly what is shown to the author — aggregate count per passage, individual reader names, or both? To be designed when vNext is planned.
- **vNext: moderator access to private quote books.** No use case identified yet for v1; worth revisiting if abuse is reported.
- **Yellow tint exact shade.** UX/design decision to be made during implementation; must be visually distinct from the annotation tint and readable on the chapter's background colour.
