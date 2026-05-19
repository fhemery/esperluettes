# Chapter Annotations — Functional Specification

## 1. Overview

Today, readers leave feedback on a chapter by writing a single root comment in the form below the chapter. This is useful for global feedback, but readers have asked for a way to comment **inline** — while they read, anchored to specific passages.

The Chapter Annotations feature lets a reader:

- Highlight a passage of a chapter and attach a short comment (an **annotation**) to it.
- Drop a quick emoji reaction (heart, fire, thumbs up) on a passage in one click.
- Accumulate annotations as they read, review them in a dedicated tab of the root-comment form, and publish them **together** with the root comment.

The chapter author can then see all annotations from all readers along the text, click to read them, and optionally reply.

## 2. Vocabulary

Used consistently throughout this document and in the codebase.

| Term | Meaning |
|------|---------|
| **Comment** | The existing root comment posted below the chapter (≥ 140 characters). |
| **Annotation** | A short inline note (≥ 1 non-whitespace character) anchored to a passage of the chapter text. Always tied to a root comment authored by the same user. |
| **Reaction** | An annotation whose body is a single emoji, created via the quick-emoji shortcut. Technically identical to an annotation; rendered differently. |
| **Reply** | A second-level note under an annotation (one level deep, same rule as comment replies today). |
| **Highlighted text** | The exact text snippet the commenter selected when creating the annotation. |
| **Anchor** | The logical position of the annotation inside the chapter content (start/end offset in the plain-text body). |
| **Draft** | An annotation that has been created locally by the user but not yet published (because the user has not submitted their root comment). |
| **Missing annotation** | An annotation whose highlighted text can no longer be located in the chapter body (because the author edited or removed it). |

## 3. User Roles & Visibility

| Role | Can create annotations | Can see whose annotations |
|------|----------------------|---------------------------|
| **Guest** (not logged in) | No | None |
| **Reader** (non-author of the chapter, non-moderator) | Yes, on chapters they are allowed to read | **Only their own** (drafts + published) |
| **Chapter author / co-author** | No (cannot comment on own chapter, by current rules) | **All published annotations** from all readers |
| **Moderator** | Yes (same as a reader on chapters they can read) | All published annotations on chapters they are allowed to view |

Guests see no annotations and have no UI affordance for creating them.

A reader's drafted annotations are stored client-side only (local storage) until the root comment is published. **Until then, no one but the reader sees them — not even the chapter author.**

## 4. Functional Requirements

### 4.1 Creating an annotation

#### Trigger
While reading a chapter, the reader selects (highlights) a passage of text. A small floating toolbar appears next to the selection, anchored to the end of the selection.

The toolbar contains, in this order:

1. A **comment icon** — opens the inline form.
2. **Three quick emoji buttons**: heart ❤️, fire 🔥, thumbs up 👍.
3. A **"+" button** — opens the inline form with the editor's emoji palette pre-focused.

#### Inline form
Clicking the comment icon (or "+") opens a small inline form just below the selection, containing:

- A simplified rich-text editor (a stripped-down version of `app/Domains/Shared/Resources/views/components/editor.blade.php`) that supports **bold**, *italic*, and **emojis** only. No headings, no links, no lists, no spoiler.
- A **Save** button.
- A **Cancel** button.

Behavior:
- Saving (button, focus-out of the editor, or pressing Enter+Ctrl/Meta) commits the annotation **as a draft to local storage**.
- Cancel discards the in-progress text and closes the form. The highlight is dropped.
- The form enforces a minimum of **1 non-whitespace character** and a maximum of **TBD (proposed: 1000 characters)** on the plain-text body.

#### Quick emoji shortcut
Clicking one of the three quick-emoji buttons (heart, fire, thumbs up):

- Immediately creates a draft annotation with that single emoji as the body.
- Does **not** open the inline form by default.
- The user can later edit the annotation (e.g., to add text after the emoji) from the review tab in the root-comment form (see §4.5).

#### Highlight constraints
- A single selection may span multiple paragraphs / blocks.
- Maximum highlighted text length: **TBD (proposed: 500 plain-text characters)**. Selections beyond the cap show a tooltip "Selection too long" and disable the toolbar.
- Empty / whitespace-only selections do not show the toolbar.
- Overlapping annotations from the same user are allowed.

### 4.2 Visualizing draft annotations (commenter, while reading)

After saving a draft annotation:

- A small **avatar of the commenter** appears anchored to the right of (or below, on smaller screens — see §4.7) the end of the highlighted passage.
- The previously highlighted text gains a subtle background highlight (e.g., a yellow underline tint) so the reader can see what they annotated.
- Clicking the avatar opens a popover (using the existing `<x-shared::popover>` component, placed right on `lg+` and bottom on `md-`) showing the annotation body, with edit / delete actions.

When the commenter returns to the chapter later, their drafts (from local storage) **and** their already-published annotations re-render in the same way.

### 4.3 Publishing: the root-comment form integration

The root-comment form below the chapter gains a new section above (or beside) the editor: a small tab strip with two tabs:

- **Comment** (current behavior) — the 140-char-minimum editor.
- **Annotations (N)** — the review tab. `N` is the count of drafts. If 0, the tab is hidden or disabled.

The annotations tab displays a vertical list of the user's drafted annotations. Each item shows:

- The highlighted text (truncated with ellipsis if long), shown as a quoted snippet.
- The annotation body (rendered HTML, supports bold/italic/emoji).
- An **Edit** action (opens the body in-place for editing).
- A **Delete** action (removes the draft).

On **submit** of the root comment:

1. The root comment is created via the existing `POST /comments` route (unchanged, ≥ 140 chars enforced).
2. **Atomically**, all draft annotations are published, attached to that newly created root comment.
3. On success, the local-storage drafts are cleared.
4. On failure (validation, network), drafts remain in local storage and the user can retry. Surface a clear error.

#### Local-storage durability
- Both the **in-progress root comment body** and the **draft annotations** are saved to local storage on every change so an accidental F5 doesn't lose work.
- Storage key includes the chapter id (e.g., `chapter:{chapterId}:draft`) and the user id, so a user logging in/out on a shared device doesn't pollute someone else's drafts.
- Storage is cleared on successful publish.

#### Constraint: cannot annotate without intending to comment
A user can save as many draft annotations as they like, but **they only become visible to anyone else when the root comment is published.** This:

- Reuses the existing 140-char "meaningful root comment" rule.
- Keeps the credit system unchanged (1 root comment = 1 credit, regardless of annotation count).
- Avoids exposing half-baked feedback to the author.

#### Locked after publish (v1)
Once a user has published their root comment for a chapter, they **cannot add, edit, or delete annotations on that chapter**. The inline toolbar no longer appears when they highlight text. This is a v1 limitation; see §10.

### 4.4 Visualizing annotations (author POV)

On viewports `md` and above, published annotations are displayed in the chapter's right column by default. On mobile (below `md`), no in-chapter UI is rendered — see §4.7.

#### Single-icon-per-line model

For each visual line that has one or more annotations, **a single icon** is placed in the right column at that line's height.

- One annotation on that line → the icon is the **commenter's avatar**.
- Multiple annotations on the same line → the icon is still **one avatar (the first commenter's, ordered by anchor offset)**, with a small **"+N" count badge** indicating the total number of annotations grouped at that line.

The annotated passage itself has a subtle background tint to indicate it carries annotations.

#### Grouping rule

Two annotations are considered "on the same line" when their **rendered icon positions are within ~20px vertically** at display time (computed in the client after layout). Annotations from different commenters and annotations with overlapping or near-overlapping anchors are grouped together when they meet this rule.

#### Clicking the icon

Clicking the icon opens a popover (`<x-shared::popover>` anchored right on `lg+`, anchored below on `md`):

- The popover shows **one annotation at a time** (the first, by anchor order, by default).
- The header displays a position indicator (`1 / N`) and `<` / `>` arrows when `N > 1`, letting the author cycle through the grouped annotations without closing the popover.
- For each annotation, the popover shows: the commenter's display name and avatar, the annotation body, the reply thread (if any), a **Reply** action, a **Report** action, and a **Mark as processed / Mark as unprocessed** action.

#### Filtering — top-right menu

A small filter menu lives in the right column, at the top (above the first annotation icon). Annotations are displayed by default — the menu only refines what's visible.

The menu offers:

- **Filter by commenter**: a checklist of all readers who have annotated this chapter. Each entry has the reader's display name, avatar, and a checkbox. All boxes are checked by default. Unchecking a reader hides their annotations from the chapter margin **and** from the bottom Annotations tab (§4.5).
- **Show processed**: a single toggle. Default **off**. When off, annotations the author has marked as processed are hidden from both the chapter margin and the bottom Annotations tab. When on, processed annotations re-appear.

Filter state is **per-session** (resets on page reload).

#### "Mark as processed"

The author can mark a **root annotation** as processed from either the in-chapter popover or the bottom Annotations tab. Behavior:

- Processed annotations are hidden from both the chapter margin and the bottom Annotations tab while the "Show processed" filter is off (default).
- The state is **reversible**: the author can mark an annotation back as unprocessed.
- The state is set on the **root annotation only**. The replies that hang off that root annotation are hidden along with it whenever the root is hidden. There is no per-reply "processed" flag.
- The processed state is **author-only**: the commenter does not see this flag, does not know whether their annotation has been processed, and cannot toggle it. It is a private workflow tool for the chapter author.

#### Co-authors share state

Co-authors of a story share the same filter and processed state for any given annotation, because "processed" is a property of the annotation, not of the viewer. Different co-authors viewing the same chapter see the same processed-state defaults.

### 4.5 Annotations under a root comment (in the comment list)

In the comments section below the chapter, root comments that have published annotations get a **second tab**:

- Tab 1: **Comment** — the existing root-comment body and its replies.
- Tab 2: **Annotations (N)** — a vertical list of that user's annotations on this chapter, each showing the highlighted text (with a "missing" badge if anchoring failed), the body, replies, and moderation actions.

Visibility of this tab:

- For the commenter themselves: always visible (with their own annotations).
- For the chapter author / moderator: visible on root comments that have annotations.
- For other readers: hidden.

#### Filter & processed-state applied here too

When the viewer is the chapter author / co-author / moderator, the filter menu (§4.4) and the processed-state default apply to the bottom Annotations tab consistently:

- The `(N)` count on each root comment's Annotations tab reflects the **filtered** count (excluding processed annotations when the "Show processed" toggle is off; excluding annotations from commenters the author has unchecked).
- "Mark as processed" / "Mark as unprocessed" is available on each annotation row in the tab, alongside Report (and, for moderators, Delete — see §9).
- On mobile, the filter UI (commenter checklist, "Show processed" toggle) is exposed at the top of the bottom Annotations tab itself, since there is no in-chapter right column to host it (§4.7).

### 4.6 Replies to annotations

The chapter author can reply to any annotation (typical use case: "good catch, fixed in next version"). The commenter can reply back. Replies are **one level deep**, identical to the existing comment-reply rule.

Visibility of replies follows the same rule as the annotation itself: only the commenter, the chapter author/co-authors, and moderators see them.

Reply UX:
- Triggered from the popover (in-chapter) or the annotation row in the bottom Annotations tab.
- Uses the same simplified editor (bold/italic/emoji only) — same length constraints as the annotation body.

### 4.7 Mobile considerations

On viewports below `md` (phones; no right column available):

- **No annotation icons or avatars are rendered in the chapter body.** This applies to *everyone* — the commenter's own drafts, the commenter's own published annotations, and the author's view of all readers' annotations. The chapter text itself remains highlighted (subtle background tint on annotated passages), but no margin or inline icons appear.
- **Creating annotations still works.** Touch-text-selection still surfaces the floating toolbar (comment icon, three quick emoji buttons, "+" button), and the inline form opens as a small overlay anchored to the selection. The commenter can save drafts as usual.
- **All review and navigation happens via the bottom comment area.**
  - The commenter reviews their drafts in the **Annotations (N)** tab of the root-comment form (§4.3).
  - The author / moderator reviews published annotations via the **Annotations (N)** tab on each root comment in the comments list (§4.5).
- **Filter UI on mobile**: the filter menu (commenter checklist + "Show processed") is exposed at the top of the bottom Annotations tab itself (rather than in the absent right column).
- **"Mark as processed"** is available on each annotation row in the bottom Annotations tab on mobile, same as desktop.

Tablet (between `md` and `lg`) falls back to the inline-avatar layout described in §4.4, except the right column doesn't exist either — so the icon is rendered **inline, right after the end of the highlighted passage**, and the popover anchors below. The filter menu lives at the top of the chapter content area in that breakpoint.

## 5. Anchoring & Re-anchoring

### 5.1 Storage
Each annotation stores:

- `highlighted_text`: the exact plain-text snippet the user selected, verbatim, at the moment of annotation.
- `anchor_start` / `anchor_end`: plain-text character offsets in the chapter body at the moment of annotation.
- `anchor_status`: `ok` (anchored), `moved` (re-anchored via fuzzy match), or `missing` (highlighted text no longer found).

### 5.2 Re-anchoring algorithm
On each chapter view (lazy) — or on `ChapterUpdated` (eager); architecture decision deferred to the technical doc:

1. Look for `highlighted_text` at exactly `anchor_start..anchor_end`. If found → `ok`.
2. Otherwise, search for `highlighted_text` near `anchor_start` (within a configurable window, e.g., ±1000 chars). If found → update offsets, set `anchor_status = moved`.
3. Otherwise, run a fuzzy match (e.g., highest-similarity substring within the chapter body, above a similarity threshold). If found → update offsets, set `anchor_status = moved`.
4. If none of the above matches → set `anchor_status = missing`.

### 5.3 Display rules
- `ok` / `moved` annotations render in the chapter and in the bottom Annotations tab as normal.
- `missing` annotations:
  - Do **not** render in the chapter body (no avatar, no highlight).
  - Render in the bottom Annotations tab with a **"Passage no longer present in the chapter"** badge.
  - Continue to display their highlighted text snippet so the commenter / author understands what was being commented on.

## 6. Constraints

| Constraint | Value |
|-----------|-------|
| Annotation body min length (plain text, non-whitespace) | **1** |
| Annotation body max length (plain text) | **Proposed: 1000** |
| Highlighted text max length (plain text) | **Proposed: 500** |
| Annotation body max length on replies | Same as annotation body |
| Number of annotations per (user, chapter) | **No hard cap** — but capped indirectly by max-length-of-highlighted-text + UI usability |
| Editor formatting allowed | Bold, italic, custom emojis only |
| Root comment min length | **140** (unchanged) |
| Root comment can be published without annotations | **Yes** (unchanged behavior) |
| Annotations can be published without a root comment | **No** — they always travel with a root comment |

## 7. Privacy Summary

| Viewer | What they see |
|--------|---------------|
| **Reader (annotation author)** | Their own drafts (local storage) + their own published annotations (DB). In-chapter UI on `md+`, bottom tab on all sizes. Does **not** see the author's "processed" flag. |
| **Chapter author / co-author** | All **published** annotations + replies. In-chapter UI on `md+`, bottom-tab listings on all sizes. Can mark/unmark "processed" — author-only state. |
| **Other readers** | Nothing. They see no annotation icons, no annotations tab, no highlight tint. |
| **Moderator** | All published annotations on chapters they can view (same visibility as author). Plus moderation actions. |
| **Guest** | Nothing. |

## 8. Notifications

Annotations do **not** trigger a separate notification.

- The existing `ChapterCommentNotification` fires on root-comment publish. Since annotations are published atomically with a root comment, the author sees them when they follow that notification — no additional channel needed.
- **Replies** to annotations follow the existing reply-notification path on the Comment domain (whatever that currently is for replies on root comments). If today's behavior is "no notification on reply," we keep it. If it's "notify the original author," we keep that too — annotations are not special.

## 9. Moderation

Annotations are reportable and moderable, with the following affordances:

| Surface | Reader action | Author action | Moderator action |
|---------|--------------|---------------|------------------|
| Inline popover (avatar click) | — | Report annotation | Report annotation |
| Bottom Annotations tab row | Report own? No — only others (which the reader cannot see anyway) | Report annotation | Report + **Delete annotation** + Empty-content |

Notes:

- The **delete** action is only available in the bottom Annotations tab, not from the inline popover, because that surface is shared with non-moderator readers/authors and we want a separate, deliberate location for destructive actions.
- A new moderation topic `chapter-annotation` is registered with `ModerationRegistry` (alongside the existing `comment` topic), with its own snapshot formatter.
- When a moderator deletes an annotation, it disappears from the chapter view immediately for everyone who had visibility on it.
- Moderating the **root comment** (existing `empty-content`, `delete-comment`) cascades to its annotations: if the root comment is deleted/emptied, all attached annotations are deleted (since annotations exist only as children of the root comment by design).

## 10. Out of Scope (v1) — Reserved for Later

- **Editing / adding / deleting annotations after the root comment is published.** v1 freezes annotations on publish. Adding "post-publish annotation management" is explicitly deferred — flag in the technical doc so the data model doesn't preclude it.
- **Surfacing "processed" state to the commenter.** v1 keeps the processed flag strictly author-side. A future enhancement could optionally show readers when their annotation has been processed, or even let the reader request a re-review.
- **In-chapter annotation icons on mobile (below `md`).** v1 deliberately has no inline UI on phones. A future enhancement could add a discreet inline indicator if user testing shows the bottom-tab-only flow is insufficient.
- **Cross-device draft sync.** Drafts live in local storage; no server-side draft persistence. If a user starts on phone and switches to desktop, drafts don't follow.
- **Annotations on already-published chapters by the same author** (no — same as today, authors cannot comment on their own chapters).
- **Annotations on stories** (only chapters in v1).
- **Threaded annotation conversations beyond one reply level** (same one-level rule as comments).
- **Persistent (user-Setting-backed) toggle** for "show annotations" on the author side — v1 is per-session.
- **Bulk operations** (e.g., "delete all my drafts for this chapter" — not needed for v1; the user can delete one by one).
- **Notifications dedicated to annotations / replies-on-annotations** beyond what's already in place for root comments.
- **Highlighting across HTML element boundaries that break formatting** — if the highlight spans across, say, a bold span boundary, the rendered highlight may visually look fragmented. We accept this in v1; refining is a polish item.

## 11. Decisions confirmed

| # | Question | Proposed default |
|---|----------|-----------------|
| 1 | Annotation body max length | 1000 plain-text chars |
| 2 | Highlighted text max length | 500 plain-text chars |
| 3 | Filter state persistence (commenter checklist + "Show processed") | Per-session (resets on reload) |
| 4 | Anchor re-computation timing | Lazy on chapter view (computed once and cached until next chapter update) |
| 5 | Multiple-annotations-per-line UX | Single icon (first commenter's avatar + "+N" badge) → popover with `1 / N` indicator and `<` / `>` arrows |
| 6 | Quick emoji icons | heart ❤️, fire 🔥, thumbs up 👍 (locked) |
| 7 | Reply to annotation: does the existing reply-notification behavior apply unchanged? | Yes |
| 8 | Cascading delete: deleting a root comment deletes its annotations | Yes |
| 9 | "Processed" flag scope | Root annotation only; reply thread inherits visibility |
| 10 | "Processed" flag visibility | Author-only — invisible to the commenter |
| 11 | "Processed" flag reversible | Yes (mark / unmark freely) |
| 12 | Grouping threshold for "same line" | Rendered icon Y-position within ~20px after layout |
| 13 | Mobile breakpoint with no in-chapter annotation UI | Below `md` |
| 14 | Tablet (`md` to `lg`): inline icon vs. no icon | Inline icon (right after the highlighted passage), filter menu at top of chapter content |
| 15 | Filter scope (commenter checklist + processed toggle) | Applies to **both** chapter margin and bottom Annotations tab consistently |
| 16 | "Filter by commenter" default state | All commenters checked (= all annotations visible) |

## 12. User Flows (illustrative)

### 12.1 Reader leaves an in-context comment + 3 annotations

1. Reader opens chapter `/stories/.../chapters/foo-42`. Sees the chapter content, no annotations from anyone else (privacy).
2. Reader highlights the sentence "He sighed and walked away." → toolbar appears.
3. Reader clicks the comment icon → inline form opens → types "This is so sad 😢" → clicks **Save** (or focuses out).
4. Draft annotation stored in local storage. Avatar appears in margin. Highlight tint applied to the sentence.
5. Reader continues reading. Highlights a later word "thunder" → clicks ❤️ in the toolbar. Draft reaction stored immediately, avatar appears.
6. Reader highlights another paragraph, opens inline form, types, hits **Cancel**. Nothing is saved; highlight cleared.
7. Reader scrolls to the bottom, opens the root-comment form. Sees "**Annotations (2)**" tab next to the editor.
8. Reader clicks the tab, reviews their 2 drafts, deletes one, edits the other.
9. Reader writes their 140-char root comment, hits **Submit**.
10. Root comment + the remaining 1 annotation are persisted atomically. Local-storage drafts cleared.
11. Page refreshes (or in-place update). The reader's annotation now appears as published, identical visual to the draft.

### 12.2 Author opens their chapter and reviews annotations

1. Author opens `/stories/.../chapters/foo-42` on desktop.
2. Right column shows a small filter menu at the top, followed by annotation icons distributed along the text. Most paragraphs are subtly tinted to indicate annotated passages.
3. One line has a single avatar (Alice's). Another line has Bob's avatar with a "+2" badge — three annotations share that line.
4. Author clicks Bob's avatar → popover opens with Bob's annotation, header showing `1 / 3` and `<` / `>` arrows. Author reads it, clicks `>` → sees Charlie's annotation, clicks `>` → sees Dana's.
5. On Charlie's annotation, author clicks **Reply** → simple editor pops up → types → sends. Reply is visible only to Charlie (and to the chapter author / co-authors / moderators).
6. On Bob's annotation, author clicks **Mark as processed** → it is now hidden from the chapter margin and from the bottom Annotations tab. (The "+2" badge becomes "+1".)
7. Author later opens the filter menu, toggles **Show processed** on → Bob's annotation reappears (with a visual hint that it's processed). Toggles off again to clean the view.
8. Author unchecks Dana in the commenter filter → Dana's annotation disappears from the margin (and from the bottom Annotations tab on Dana's root comment).
9. Author scrolls to comments section. Each root comment that has at least one annotation (after filtering) shows a second tab "**Annotations (N)**" with N = count after filtering.

### 12.3 Author edits the chapter; one passage is deleted

1. Author edits chapter, removes a paragraph that had 5 annotations from various readers.
2. On the next view (lazy re-anchoring), the 5 annotations fail the exact + fuzzy match → marked `missing`.
3. They no longer render in the chapter body.
4. In the bottom Annotations tab under each affected commenter's root comment, the annotation entries still appear with a **"Passage no longer present"** badge and the original highlighted text snippet preserved.

### 12.4 Reader reports an annotation; moderator deletes it

1. Reader (with visibility on the annotation — i.e., its author themselves, or the chapter author, or a moderator) clicks **Report** on the annotation, picks a reason.
2. The report flows through `ModerationRegistry` with the new `chapter-annotation` topic.
3. Moderator reviews the report in the admin panel.
4. Moderator opens the chapter (or the bottom Annotations tab) and clicks **Delete annotation** — only available from the bottom-tab surface for moderators.
5. Annotation is soft-deleted. Disappears from the chapter and the bottom tab for everyone.

---

## Next steps

Once this spec is locked in, the architecture document (`Chapter_Annotations_Architecture.md` in the same folder) will cover:

- Domain ownership (annotations live in the Comment domain or in Story?).
- Data model (a new `chapter_annotations` table vs. reusing `comments`).
- Public API additions on `CommentPublicApi` (or a new one).
- Selection → anchor offset conversion in JS.
- Re-anchoring algorithm specifics & where it runs.
- Local-storage draft schema + key conventions.
- Inline-form Blade component (simplified editor reuse strategy).
- Moderation topic registration.
- Event additions (e.g., `AnnotationPosted`, `AnnotationDeletedByModeration`) and their consumers.
- Migration plan & backfill (none expected — new feature, no historical data).
- Testing strategy (integration tests for publish flow, anchor re-computation, visibility rules).
