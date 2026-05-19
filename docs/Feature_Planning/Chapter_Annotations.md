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
| **Anchor** | The logical position of the annotation inside the chapter content. Stored as a verbatim plain-text snippet plus a short plain-text prefix and suffix (the "surrounding context"). Re-located client-side on each chapter view (see §5). |
| **Draft** | An annotation that has been created locally by the user but not yet published (because the user has not submitted their root comment). |
| **Pending change** | An annotation modification (add / edit / delete) made by the commenter **after** they published their root comment, not yet saved to the server. |
| **Missing annotation** | An annotation whose anchor (highlighted text + surrounding context) can no longer be located in the chapter body (because the author edited or removed it). |

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

#### Modifying annotations after publish

Once the root comment is published, the user can still **add**, **edit**, and **delete** annotations on that chapter. The UX is identical to the pre-publish flow (highlight → toolbar; click own avatar → edit / delete) with two differences:

- Changes are not committed immediately. They accumulate as **pending changes** in local storage.
- A floating banner appears at the bottom of the viewport: **"Vous avez {N} annotations non sauvegardées"** with a **Save** button. The banner is sticky while at least one pending change exists; it disappears when there are none.

On clicking **Save**:

1. All pending changes are sent in a single atomic request that adjusts annotations attached to the existing root comment. The root comment body itself is untouched.
2. On success, local-storage pending changes are cleared.
3. On failure, pending changes remain in local storage and the banner stays.

Pending changes survive page reloads (local storage), keyed by `chapter:{chapterId}:pending:{userId}`.

No notification is emitted on add / edit / delete of an annotation, even after publish. See §8.

### 4.4 Visualizing annotations (author POV)

On viewports `md` and above, published annotations are displayed in the chapter's right column by default. On mobile (below `md`), no in-chapter UI is rendered — see §4.7.

#### Single-icon-per-line model

For each visual line that has one or more annotations, **a single icon** is placed in the right column at that line's height.

- One annotation on that line → the icon is the **commenter's avatar**.
- Multiple annotations on the same line → the icon is still **one avatar (the first visible commenter's, in document reading order)**, with a small **"+N" count badge** indicating the total number of **root annotations** (replies are not counted) grouped at that line. The "first" computation skips annotations that are currently hidden (filtered-out commenter, processed-while-toggle-off).

The annotated passage itself has a subtle background tint to indicate it carries annotations.

#### Grouping rule

Two annotations are considered "on the same line" when their **rendered icon positions are within ~20px vertically** at display time (computed in the client after layout). Annotations from different commenters and annotations with overlapping or near-overlapping anchors are grouped together when they meet this rule.

#### Clicking the icon

Clicking the icon opens a popover (`<x-shared::popover>` anchored right on `lg+`, anchored below on `md`):

- The popover shows **one annotation at a time** (the first, by anchor order, by default).
- The header displays a position indicator (`1 / N`) and `<` / `>` arrows when `N > 1`, letting the author cycle through the grouped annotations without closing the popover.
- For each annotation, the popover shows: the commenter's display name and avatar, the annotation body, the reply thread (if any), a **Reply** action, and a **Mark as processed / Mark as unprocessed** action.
- There is **no Report action in the inline popover**. Moderation reports target the root comment, not the annotation (see §9).
- **Mark as processed** and **Reply** are immediate AJAX calls — no batching, no Save banner. The UI updates on the fly (badges adjust, avatar may switch to the next visible commenter, popover closes if the last grouped annotation becomes hidden).

#### Filtering — top-right menu

A small filter menu lives in the right column, at the top (above the first annotation icon). Annotations are displayed by default — the menu only refines what's visible.

The menu offers:

- **Filter by commenter**: a checklist of all readers who have annotated this chapter. Each entry has the reader's display name, avatar, and a checkbox. All boxes are checked by default. Unchecking a reader hides their annotations from the right margin. (The per-commenter pop-up in §4.5 is unaffected — it has no filter.)
- **Show processed**: a single toggle. Default **off**. When off, annotations the author has marked as processed are hidden from the right margin. When on, processed annotations re-appear.

Filter state is **per-session** (resets on page reload). Filter scope: right margin only.

#### "Mark as processed"

The author can mark a **root annotation** as processed from the in-chapter popover (§4.4) or from a row in the per-commenter pop-up (§4.5). Both call paths are immediate AJAX. Behavior:

- Processed annotations are hidden from the **right margin** when the "Show processed" filter is off (default). They remain visible in the per-commenter pop-up (with a visual "processed" marker on the row), regardless of the filter.
- The state is **reversible**: the author can mark an annotation back as unprocessed.
- The state is set on the **root annotation only**. The replies that hang off that root annotation are hidden along with it whenever the root is hidden. There is no per-reply "processed" flag.
- The processed state is **author-only**: the commenter does not see this flag, does not know whether their annotation has been processed, and cannot toggle it. It is a private workflow tool for the chapter author.

#### Co-authors share state

Co-authors of a story share the same filter and processed state for any given annotation, because "processed" is a property of the annotation, not of the viewer. Different co-authors viewing the same chapter see the same processed-state defaults.

### 4.5 Annotations under a root comment (in the comment list)

In the comments section below the chapter, each root comment that has at least one published annotation displays an **"N annotations"** button placed **just above the comment body** (between the comment header / author line and the comment content). Clicking the button opens a **pop-up modal** showing that commenter's annotations on this chapter — and only that commenter's.

The pop-up lists each annotation as a row:

- The **highlighted text** rendered as a quoted blockquote (plain text, no HTML — see §5).
  - When the anchor is `missing`, the blockquote still shows the original snippet, with a **"Passage no longer present in the chapter"** badge below it.
- The **annotation body** (rendered HTML, bold/italic/emoji).
- The **reply thread**, threaded one level deep, if any.
- Per-viewer actions (see below).

Because the pop-up scopes to one commenter at a time, **there is no filter UI in this surface**. The filter menu (commenter checklist + "Show processed") lives only in the desktop right margin (§4.4).

#### Button visibility & count

- For the **commenter themselves**: the button appears on their own root comment whenever they have at least one annotation. The count is their total annotation count on the chapter (unfiltered).
- For the **chapter author / co-author / moderator**: the button appears on any root comment that has at least one annotation. The count is the total annotation count from that commenter on the chapter (unfiltered — the filter menu does **not** apply here).
- For **other readers**: the button is hidden.

#### Actions inside the pop-up

| Viewer | Per-annotation actions |
|--------|------------------------|
| **Commenter** (own annotations) | Edit, Delete — both queue as pending changes and require Save (§4.3). Reply to the author's reply if one exists (immediate AJAX). |
| **Chapter author / co-author** | Reply, Mark as processed / unprocessed — both immediate AJAX. |
| **Moderator** | Delete annotation, Empty content — immediate AJAX. (No Reply.) |

Moderation actions for annotations are **only available from this pop-up**, never from the in-chapter right-margin popover. Reports themselves still target the **root comment** (existing comment-moderation flow) — there is no per-annotation "Report" action anywhere.

Processed annotations remain visible inside the pop-up (with a visual "processed" marker on the row); the "Show processed" filter only affects the desktop right-margin display. This keeps the pop-up a complete audit view per commenter.

### 4.6 Replies to annotations

The chapter author / co-authors can reply to any annotation (typical use case: "good catch, fixed in next version"). The commenter can reply back. Replies are **one level deep**, identical to the existing comment-reply rule.

Visibility of replies follows the same rule as the annotation itself: only the commenter and the chapter authors/co-authors/moderators see them. **Moderators see replies but cannot post replies** — their only actions are moderation (§9).

Reply UX:
- Triggered from the in-chapter popover (§4.4) or from the per-commenter pop-up under the root comment (§4.5).
- Uses the same simplified editor (bold/italic/emoji only) — same length constraints as the annotation body.
- Replies are committed **immediately via AJAX** (not batched in pending changes).

Because annotation replies do **not** emit a notification (§8), after the author posts a reply the UI should surface a small hint encouraging them to also post a regular reply on the root comment if they want the commenter to be notified.

### 4.7 Mobile considerations

On viewports below `md` (phones; no right column available):

- **No annotation icons or avatars are rendered in the chapter body.** This applies to *everyone* — the commenter's own drafts, the commenter's own published annotations, and the author's view of all readers' annotations. The chapter text itself remains highlighted (subtle background tint on annotated passages), but no margin or inline icons appear.
- **Creating annotations still works.** Touch-text-selection still surfaces the floating toolbar (comment icon, three quick emoji buttons, "+" button), and the inline form opens as a small overlay anchored to the selection. The commenter can save drafts as usual.
- **All review and navigation happens via the bottom comment area.**
  - The commenter reviews their drafts via the **Annotations (N)** tab in the root-comment form (§4.3, pre-publish flow only).
  - Post-publish, everyone reviews annotations via the **"N annotations"** button on each root comment (§4.5).
- **There is no filter UI on mobile.** The right-margin filter menu does not exist on phones, and the per-commenter pop-up has no filter by design (§4.5).

Tablet (between `md` and `lg`) falls back to the inline-avatar layout described in §4.4, except the right column doesn't exist either — so the icon is rendered **inline, right after the end of the highlighted passage**, and the popover anchors below. The filter menu lives at the top of the chapter content area in that breakpoint.

## 5. Anchoring & Re-anchoring

Anchors are **context-based**, **plain-text**, and re-located **client-side** on every chapter view. The server never re-computes anchors — it only stores them.

### 5.1 Storage

Each annotation stores three plain-text strings, captured at creation time from the chapter body **after HTML tags are stripped** (see §5.4):

- `highlighted_text`: the exact plain-text snippet the user selected, verbatim.
- `prefix`: the plain-text immediately preceding the selection — **up to 5 words** before. May be empty if the selection starts at the very beginning of the chapter; empty-on-one-side is acceptable.
- `suffix`: the plain-text immediately following the selection — **up to 5 words** after. May be empty if the selection ends at the very end of the chapter; empty-on-one-side is acceptable.

These three fields are immutable after annotation creation. They are never re-written by re-anchoring; they are the ground truth used to find the passage again.

### 5.2 Re-anchoring algorithm (client-side, runs on each chapter view)

The client builds a single canonical plain-text view of the chapter (§5.4), then for each annotation:

1. Search for the full triple `prefix + highlighted_text + suffix`. If at least one match exists → anchor is `ok`; the **first** match (in document order) becomes the rendered highlight. Collisions (the exact same triple appearing twice — rare in prose) are accepted in v1 (see §11).
2. Otherwise, search for the pair `prefix … suffix` — find the (prefix, suffix) pair anywhere in the chapter and accept any text in between as the "moved" highlighted range. If exactly one match exists → anchor is `moved`. The displayed highlight may now cover a different passage than `highlighted_text`, but the original `highlighted_text` is still what we render in the pop-up blockquote.
3. If neither step matches, or step 2 matches more than once ambiguously → anchor is `missing`.

No similarity threshold, no fuzzy match. The algorithm is deterministic and cheap.

### 5.3 Display rules

- `ok` / `moved` annotations render in the chapter body (highlight tint + avatar in the right margin on `md+`, tint only on mobile) and in the per-commenter pop-up (§4.5) as normal.
- `missing` annotations:
  - Do **not** render in the chapter body (no highlight, no avatar).
  - Render in the per-commenter pop-up with a **"Passage no longer present in the chapter"** badge.
  - Continue to display their `highlighted_text` snippet in the blockquote so the commenter / author understands what was originally being commented on.

### 5.4 Canonical plain-text view of the chapter

To make selection capture (creation time) and re-anchoring (later) consistent, both sides operate on the same plain-text projection of the chapter body:

- HTML tags are stripped — only text nodes contribute.
- Custom emoji blots (`<span class="ql-custom-emoji-{name}">`) are replaced by `:{name}:` in the canonical view (e.g., `:fire:`, `:heart:`). This makes them addressable, selectable, and stable across re-renders, and it survives the round-trip into the blockquote shown in the per-commenter pop-up.
- Whitespace is preserved as it appears in the DOM (no collapsing beyond what the browser already does).
- Block boundaries (`</p>`, `</blockquote>`, etc.) contribute a single space, so two adjacent paragraphs don't fuse word-wise.

Only the chapter body is annotatable — never the `author_note` or any other chrome.

## 6. Constraints

| Constraint | Value |
|-----------|-------|
| Annotation body min length (plain text, non-whitespace) | **1** |
| Annotation body max length (plain text) | **Proposed: 1000** |
| Highlighted text max length (plain text) | **Proposed: 500** |
| Annotation body max length on replies | Same as annotation body |
| Number of annotations per (user, chapter) | **No hard cap** — but capped indirectly by max-length-of-highlighted-text + UI usability |
| Editor formatting allowed | Bold, italic, custom emojis only. Implemented by extending `editor.blade.php` with an explicit list-of-options prop (the set of toolbar features to expose) that propagates down to `editor-bundle.js` — replacing the current ad-hoc `withHeadings` / `withLinks` / `withSpoiler` toggles. |
| Anchor context size (each side) | Up to 5 words |
| Root comment min length | **140** (unchanged) |
| Root comment can be published without annotations | **Yes** (unchanged behavior) |
| Annotations can be published without a root comment | **No** — they always travel with a root comment |

## 7. Privacy Summary

| Viewer | What they see |
|--------|---------------|
| **Reader (annotation author)** | Their own drafts (local storage), their own pending post-publish changes (local storage), and their own published annotations (DB). In-chapter UI on `md+` (right-margin avatars), pre-publish "Annotations (N)" tab in the root-comment form, "N annotations" button → pop-up on their own root comment after publish. Does **not** see the author's "processed" flag. |
| **Chapter author / co-author** | All **published** annotations + replies. In-chapter UI on `md+` (right-margin avatars + popover), "N annotations" button → pop-up on each root comment that has annotations. Can mark/unmark "processed" — author-only state. |
| **Other readers** | Nothing. They see no annotation icons, no annotations tab, no highlight tint. |
| **Moderator** | All published annotations on chapters they can view (same visibility as author). Plus moderation actions. |
| **Guest** | Nothing. |

## 8. Notifications

Annotations and annotation replies **never** trigger a notification, in v1. Specifically:

- Publishing a root comment with N annotations: the existing `ChapterRootCommentNotification` fires (sent to all story authors), exactly as it does today for a root comment with zero annotations. The annotations themselves are not surfaced in the notification — the author sees them when they open the chapter.
- Editing / adding / deleting an annotation after publish (§4.3): no notification.
- Replying to an annotation (author → commenter, or commenter → author): no notification. This is a deliberate v1 choice (see Q1 decision in §11). To compensate, the UI nudges the author after they reply to an annotation to also post a regular reply on the root comment if they want the commenter to be notified — that existing path fires `ChapterReplyCommentNotification` and reaches the commenter.

## 9. Moderation

Annotations are reportable in their own right. A new moderation topic **`chapter-annotation`** is registered with `ModerationRegistry` (alongside the existing `comment` topic), with its own snapshot formatter and its own dedicated set of moderation reasons.

Moderation actions available on individual annotations:

| Surface | Commenter | Chapter author / co-author | Moderator |
|---------|-----------|---------------------------|-----------|
| Right-margin popover (§4.4, desktop / tablet only) | — | Reply, Mark as processed/unprocessed | — |
| Per-commenter pop-up (§4.5) | Edit, Delete (queued — Save banner) | Reply, Mark as processed/unprocessed, **Report** | **Delete annotation**, **Empty content** |

Notes:

- The **Report** action opens the existing moderation report modal, targeting the new `chapter-annotation` topic with the annotation ID as `entity_id`. No new HTTP route in Comment domain is required — the existing `POST /moderation/report` endpoint handles it.
- Moderator actions on annotations live **only in the per-commenter pop-up**, never in the right-margin popover. The right-margin popover is the live-triage surface for the author and never carries destructive actions.
- Moderator actions are **immediate AJAX** — no batching.
- When a moderator deletes an annotation (or empties its content), the change is visible to all viewers who had visibility on it on their next render of the chapter / pop-up.
- Moderating the **root comment** (existing `empty-content`, `delete-comment`) cascades to its annotations: if the root comment is deleted/emptied, all attached annotations are deleted, since annotations exist only as children of the root comment by design.
- The `chapter-annotation` topic ships with a small set of seeded default reasons; admins manage the full list via the existing moderation-reasons admin panel.

## 10. Out of Scope (v1) — Reserved for Later

- **Surfacing "processed" state to the commenter.** v1 keeps the processed flag strictly author-side. A future enhancement could optionally show readers when their annotation has been processed, or even let the reader request a re-review.
- **In-chapter annotation icons on mobile (below `md`).** v1 deliberately has no inline UI on phones. A future enhancement could add a discreet inline indicator if user testing shows the pop-up-only flow is insufficient.
- **Cross-device draft / pending-change sync.** Drafts and post-publish pending changes live in local storage; no server-side draft persistence. If a user starts on phone and switches to desktop, drafts/pending edits don't follow.
- **Annotations on already-published chapters by the same author** (no — same as today, authors cannot comment on their own chapters).
- **Annotations on stories** (only chapters in v1).
- **Threaded annotation conversations beyond one reply level** (same one-level rule as comments).
- **Persistent (user-Setting-backed) toggle** for "show annotations" on the author side — v1 is per-session.
- **Bulk operations** (e.g., "delete all my drafts for this chapter" — not needed for v1; the user can delete one by one).
- **Notifications dedicated to annotations / replies-on-annotations.** v1 emits no annotation-related notifications.
- **Highlighting across HTML element boundaries that break formatting** — if the highlight spans across, say, a bold span boundary, the rendered highlight may visually look fragmented. We accept this in v1; refining is a polish item.
- **Re-anchoring on the server.** v1 does it purely client-side. A future enhancement could persist re-anchored state if it becomes useful (e.g., for analytics).
- **Domain events on annotations.** v1 emits no events for annotation lifecycle. We may later introduce `CommentAnnotationsAdded` / `CommentAnnotationsUpdated` (and similar) for other domains to react to — to be designed when a concrete consumer surfaces.

## 11. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Annotation body max length | 1000 plain-text chars |
| 2 | Highlighted text max length | 500 plain-text chars |
| 3 | Filter state persistence (commenter checklist + "Show processed") | Per-session (resets on reload) |
| 4 | Anchor model | Context-based (prefix + highlight + suffix, plain text); re-located client-side on every chapter view; no server-side re-anchoring |
| 5 | Anchor context size | Up to 5 words on each side; may be shorter (including 0 on one side) at chapter boundaries. Duplicate-triple collisions in v1 resolve to the first document-order match — accepted risk, will revisit if it hurts |
| 6 | Multiple-annotations-per-line UX | Single icon (first commenter's avatar by anchor order + "+N" badge counting root annotations only) → popover with `1 / N` indicator and `<` / `>` arrows. When the lead annotation becomes hidden, icon switches to next visible; badge recomputes on the fly |
| 7 | Quick emoji icons | heart ❤️, fire 🔥, thumbs up 👍 (locked) |
| 8 | Notifications for annotations / annotation replies | None in v1 |
| 9 | Cascading delete: deleting a root comment deletes its annotations | Yes |
| 10 | "Processed" flag scope | Root annotation only; reply thread inherits visibility |
| 11 | "Processed" flag visibility | Author-only — invisible to the commenter |
| 12 | "Processed" flag reversible | Yes (mark / unmark freely) |
| 13 | Grouping threshold for "same line" | Rendered icon Y-position within ~20px after layout |
| 14 | Mobile breakpoint with no in-chapter annotation UI | Below `md` |
| 15 | Tablet (`md` to `lg`): inline icon vs. no icon | Inline icon (right after the highlighted passage), filter menu at top of chapter content |
| 16 | Filter scope (commenter checklist + processed toggle) | Right-margin only; per-commenter pop-up has no filter |
| 17 | "Filter by commenter" default state | All commenters checked (= all annotations visible) |
| 18 | Editing annotations after publish | Allowed; batched as pending changes saved via a "Vous avez {N} annotations non sauvegardées" banner. Atomic per-save. No notifications emitted |
| 19 | Reply / Mark-as-processed / Moderator-delete commit semantics | Immediate AJAX (not batched) |
| 20 | Annotation reports | Per-annotation Report action exposed in the per-commenter pop-up. New `chapter-annotation` moderation topic with its own dedicated, admin-managed reasons (default set seeded). Submission reuses the existing `POST /moderation/report` endpoint |
| 21 | Bottom-comment-list annotation surface | A single **"N annotations"** button next to each root comment that has annotations; click opens a pop-up listing that commenter's annotations only |
| 22 | Annotatable chapter regions | Chapter body only — `author_note` is not annotatable |
| 23 | Annotation editor formatting | Bold, italic, custom emojis only. Delivered by refactoring `editor.blade.php` to accept an explicit list-of-toolbar-options prop (propagated to `editor-bundle.js`), replacing the current `withHeadings` / `withLinks` / `withSpoiler` toggles |
| 24 | Save trigger for in-progress annotation body | Save button or Ctrl/Cmd+Enter only (focus-out does **not** auto-save) |

## 12. User Flows (illustrative)

### 12.1 Reader leaves an in-context comment + 3 annotations

1. Reader opens chapter `/stories/.../chapters/foo-42`. Sees the chapter content, no annotations from anyone else (privacy).
2. Reader highlights the sentence "He sighed and walked away." → toolbar appears.
3. Reader clicks the comment icon → inline form opens → types "This is so sad 😢" → clicks **Save** (or focuses out).
4. Draft annotation stored in local storage. Avatar appears in margin. Highlight tint applied to the sentence.
5. Reader continues reading. Highlights a later word "thunder" → clicks ❤️ in the toolbar. Draft reaction stored immediately, avatar appears.
6. Reader highlights another paragraph, opens inline form, types, hits **Cancel**. Nothing is saved; highlight cleared.
7. Reader scrolls to the bottom, opens the root-comment form. Sees an **"Annotations (2)"** tab next to the editor (pre-publish flow only).
8. Reader clicks the tab, reviews their 2 drafts, deletes one, edits the other.
9. Reader writes their 140-char root comment, hits **Submit**.
10. Root comment + the remaining 1 annotation are persisted atomically. Local-storage drafts cleared.
11. Page refreshes (or in-place update). The reader's annotation now appears as published, identical visual to the draft.
12. Later that day, the reader returns to the chapter, highlights a new sentence, adds an annotation. A floating banner appears: **"Vous avez 1 annotation non sauvegardée"** with a **Save** button. They click Save → the new annotation is committed atomically to their existing root comment. No notification is fired.

### 12.2 Author opens their chapter and reviews annotations

1. Author opens `/stories/.../chapters/foo-42` on desktop.
2. Right column shows a small filter menu at the top, followed by annotation icons distributed along the text. Most paragraphs are subtly tinted to indicate annotated passages.
3. One line has a single avatar (Alice's). Another line has Bob's avatar with a "+2" badge — three annotations share that line.
4. Author clicks Bob's avatar → popover opens with Bob's annotation, header showing `1 / 3` and `<` / `>` arrows. Author reads it, clicks `>` → sees Charlie's annotation, clicks `>` → sees Dana's.
5. On Charlie's annotation, author clicks **Reply** → simple editor pops up → types → sends. Reply is visible only to Charlie (and to the chapter author / co-authors / moderators).
6. On Bob's annotation, author clicks **Mark as processed** → it is hidden from the chapter margin (the "+2" badge becomes "+1"). It remains visible in the per-commenter pop-up with a "processed" marker.
7. Author later opens the filter menu, toggles **Show processed** on → Bob's annotation reappears (with a visual hint that it's processed). Toggles off again to clean the view.
8. Author unchecks Dana in the commenter filter → Dana's annotation disappears from the right margin. (Dana's annotations remain visible in the bottom-comments pop-up when the author opens it — the filter scope is right-margin only.)
9. Author scrolls to comments section. Each root comment that has at least one annotation shows an **"N annotations"** button next to the comment body. Author clicks it → pop-up opens listing that commenter's annotations on the chapter (blockquoted highlighted text + body + reply thread).

### 12.3 Author edits the chapter; one passage is deleted

1. Author edits chapter, removes a paragraph that had 5 annotations from various readers.
2. On the next view, the client builds the canonical plain-text view of the chapter and tries each annotation's `prefix + highlight + suffix`, then `prefix + ??? + suffix`. The 5 annotations match neither → marked `missing`.
3. They no longer render in the chapter body.
4. In each affected commenter's pop-up (opened from the bottom comments section), the annotations still appear with a **"Passage no longer present"** badge and the original highlighted text snippet preserved in the blockquote.

### 12.4 Reader reports an annotation; moderator deletes it

1. A viewer with visibility on the chapter / comment (typically the chapter author) reports the **root comment** via the existing comment-moderation flow. There is no per-annotation report action.
2. The report flows through `ModerationRegistry` with the existing `comment` topic.
3. Moderator reviews the report in the admin panel and opens the chapter.
4. Moderator scrolls to the comments section, clicks the **"N annotations"** button on the reported root comment, and uses **Delete annotation** or **Empty content** on the offending row in the pop-up — these actions are immediate (AJAX).
5. Annotation is soft-deleted. Disappears from the chapter margin and the pop-up for everyone on next render.

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
