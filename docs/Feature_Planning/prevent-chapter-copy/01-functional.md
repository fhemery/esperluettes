# Prevent copying chapter text — functional specification

## 1. Overview

On the reader chapter page, readers can still select text (annotations and
quotes depend on it) but can no longer copy it out with the usual gestures
(Ctrl/Cmd+C, Ctrl/Cmd+X, drag-out, context-menu copy). The story's authors and
co-authors are not restricted. This is a deterrent against casual copying, not
a security guarantee.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Texte du chapitre | the chapter body inside the reader's `data-quote-article` element |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | chapter (as today) | select; copy blocked |
| `user` / `user-confirmed` (incl. beta readers) | chapter | select, annotate, quote; copy blocked |
| Author / co-author | chapter | select and copy freely |
| Moderator / Admin | chapter | copy blocked (not authors) |

## 4. Functional requirements

### 4.1 Reader tries to copy
1. Reader selects a passage: selection works, the annotation / quote toolbars appear as today.
2. Reader presses Ctrl/Cmd+C or X, uses the context menu's copy, or drags the selection: nothing is copied, no message is shown.
3. Copying from anywhere other than the chapter body (comments, author note, title) still works.

### 4.2 Author
Authors and co-authors get no restriction on their own chapters.

## 5. Lifecycle

N/A — stateless, nothing is stored.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | see §3; guard applies when the viewer is not an author of the story |
| Visibility / privacy | unchanged |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | no user-facing text |
| Mobile | long-press selection must keep working (annotations rely on it) |
| Accessibility | screen readers and keyboard selection unaffected; `user-select` left untouched |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Keep selection? | Yes (request) |
| 2 | Authors exempt? | Yes (request: "ideally") |

## 8. Out of scope

- Real protection against copying (view-source, dev tools, screenshots, print, reader mode, feeds).
- Changing the copy behaviour of news, static pages, comments, or the author note.
- Any notice shown to the reader.

## 9. Open questions

- Should the context menu be blocked? Assumed no: a `copy` event handler covers the menu's copy entry and keeps mobile long-press selection intact. Non-blocking.
