# Chapters — MultiEdit content — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Chapter content moves from a single rich-text field to the MultiEdit editor, so
an author can interleave text and images inside a chapter the way News already
does. The move is **opt-in per chapter and involves no data migration**: a
chapter stays in Simple mode — identical to today — until its author switches it
to Advanced. Every chapter nobody converts renders byte-identical HTML, which is
what keeps readers' existing quotes anchored.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Simple mode (`Simple`) | The chapter is a single rich-text body, exactly as today. `content_blocks` is NULL. The default for new and legacy chapters. |
| Advanced mode (`Avancé`) | The chapter is an ordered list of blocks. `content_blocks` holds them. |
| Block (`bloc`) | One unit of chapter content — either `text` or `image`. No other type exists. |
| Rendered content | The HTML the reading page prints, stored in `story_chapters.content`. Derived from the blocks in Advanced mode; the author's own HTML in Simple mode. |
| Conversion | An author switching a chapter from Simple to Advanced. |

User-facing strings for the toggle and block controls are French and already
exist in `editor::multi.*` — this feature introduces no new vocabulary for the
reader, who sees no change at all.

## 3. Roles & visibility

Nothing here is new. Chapter editing already sits behind `role:user-confirmed`
middleware plus `ChapterPolicy` ownership; MultiEdit inherits that gate
unchanged and adds no rule of its own.

| Role | Can see | Can do |
|------|---------|--------|
| Guest | The rendered chapter, if published — unchanged | Nothing |
| `user` (non-confirmed) | The rendered chapter, if published — unchanged | **Cannot edit chapters at all** (route middleware), so cannot reach either mode |
| `user-confirmed`, not an author | The rendered chapter, if published | Nothing on this chapter |
| Author / co-author of the story | The edit form | Switch modes, add/reorder/delete blocks, upload and reuse images |
| Moderator | The chapter snapshot in the moderation panel — unchanged | Unchanged |
| Admin | Unchanged | Unchanged |

"Author" means any `story_collaborators` row with role `author`, not only the
story's creator — co-authors get Advanced mode on the same terms.

**Readers see no difference whatsoever.** A converted chapter and an
unconverted one are, from the reading page, the same kind of page.

## 4. Functional requirements

### 4.1 Editing a chapter that has never been converted

1. The author opens the chapter edit form.
2. The content field is the MultiEdit component in **Simple** mode, with the
   `links` toolbar — the same formatting options as today.
3. The author writes and saves as they always have.
4. `content_blocks` stays NULL. `content` holds their HTML. The rendered page is
   unchanged, so any quotes on this chapter are unaffected.

New chapters also start in Simple mode.

### 4.2 Converting a chapter to Advanced

1. The author clicks **Avancé**.
2. The chapter's existing HTML becomes a single text block.
3. The author may now add image blocks, add further text blocks, reorder them
   and delete them.
4. On save, the blocks are stored and the rendered content is recomputed from
   them.
5. The reading page prints that rendered content. Readers' quotes that spanned a
   point the author split or illustrated may silently stop matching — this is
   accepted (decision 4) and no warning or notification is shown.

### 4.3 Returning to Simple

Allowed only when the chapter has exactly one text block and no images — the
editor component already enforces this and disables the button otherwise, with
its own French tooltip. On save, `content_blocks` returns to NULL and `content`
keeps the text.

### 4.4 Images in a chapter

1. Images upload under the scope `chapters/{userId}` of the **acting** author.
   On a co-authored story this means a chapter may legitimately hold images from
   more than one author's folder.
2. The reuse picker offers an author their own chapter images only — so a
   separator used in ten chapters is uploaded once and reused.
3. **Alt text is required** on every image block; saving without it is a
   validation error, as in News.
4. The same image may be repeated any number of times within a chapter.
5. Images are not quotable and not annotable. Only text is.

### 4.5 Rendering

1. All chapter text renders inside a **single** `[data-quote-article]` root, as
   today — *not* one root per block. The per-block `[data-annotable]` idea
   sketched in the pre-loop architecture is explicitly not done here (§8).
2. The reading page's typography (`prose rich-content [text-indent:2rem]`) must
   look the same for a converted chapter as for an unconverted one.

### 4.6 Word and character counts

1. Counts are computed from **text blocks only**. Image captions and alt text do
   not count (decision 6).
2. **Converting a chapter without changing a word must not change its
   `word_count` or `character_count`.** This is an acceptance criterion, not an
   aspiration: those counts feed `TotalWordsStatistic`, `UserTotalWordsStatistic`
   and their chapter equivalents, all of which are user-visible. A conversion
   that moved a member's public word total would be a bug.

### 4.7 Media garbage collection

1. Story registers a usage provider reporting every image path referenced by any
   chapter's blocks.
2. It must be **exhaustive**. Registering the provider is what makes
   `chapters/*` a claimed folder, and only claimed folders are ever swept — so a
   path the provider fails to report becomes a deletable live image. Under-
   reporting destroys data; not registering at all merely leaks it.

## 5. Lifecycle

| Event | Behaviour |
|-------|-----------|
| Chapter edited | `content` is recomputed from the blocks on every save. It is derived data with exactly one writer; nothing else may write it. |
| Chapter deleted | Its blocks go with it. The referenced images stop being reported and become GC-eligible after the grace window. **If chapters are soft-deleted, the provider must still report a soft-deleted chapter's paths** — otherwise restoring one restores a chapter with dead images. See §9. |
| Story deleted | Same, for all its chapters. |
| Chapter unpublished / scheduled | No effect. Draft and scheduled chapters keep their blocks and their images stay referenced. |
| Author deactivated or deleted | No effect on the content. Images are reported by path, not by uploader, so a chapter surviving under a co-author keeps its images even though they sit in the deleted user's scope folder. |
| Moderation empties a chapter's content | Existing behaviour; must also clear the blocks so the two cannot disagree. |

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Unchanged — inherits `role:user-confirmed` + `ChapterPolicy`. Co-authors included. No new rule. |
| Visibility / privacy | N/A — chapter visibility is untouched; blocks are exactly as public or private as the chapter that holds them. |
| Settings | N/A — no user preference. The mode is a property of the chapter, not of the author. |
| Notifications | None. Explicitly none on conversion or on quote breakage (decision 4). The existing ReadList "chapter modified" notification is unaffected — it reads title and slug only. |
| Domain events | Unchanged. `ChapterSnapshot` carries derived counts, never raw content, so its shape does not change — but the counts it computes must follow §4.6. |
| Statistics | No new metric. Four existing ones (`TotalWords`, `TotalChapters`, and their per-user forms) consume the counts and must not shift on conversion — §4.6. |
| Moderation | Unchanged. The snapshot treats content as an opaque HTML string and keeps working because rendered content is still stored. Individual blocks are not separately reportable. |
| Lifecycle / cascade | §5. The soft-delete question in §9 is the one open item. |
| Media | Scope `chapters/{userId}`, already supported by `MediaService`. Alt text required. Story's first `MediaUsageProvider` — must be exhaustive (§4.7). |
| Search | N/A — chapter content is not indexed by the Search domain today, and this does not change that. |
| i18n | No new Story strings for the toggle or block controls; reuse `editor::multi.*`. Any new validation message goes in `story::validation`. French only. |
| Mobile | The MultiEdit component's existing responsive behaviour, unchanged. Reading page unchanged. |
| Accessibility | Alt text required on every image — enforced server-side, not merely prompted. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 3 | How do existing chapters move to MultiEdit? | The News model: nullable `content_blocks`, `content` kept as rendered output, NULL = Simple mode. **No data migration.** No existing chapter changes its rendered HTML. |
| 4 | What happens to readers' quotes when an author converts or splits a chapter? | Accept the breakage silently. No warning, no notification. It is the same class of event as an author editing their prose, which already breaks quotes today. |
| 5 | Does `author_note` get MultiEdit too? | No — stays simple rich-text. Out of scope. |
| 6 | Which toolbar for chapter text blocks? | `links`, the same preset chapters use today. Changing the formatting options is a separate decision. |
| 7 | Do image captions count toward word count? | No — counts come from text blocks only. |
| 8 | Store the rendered content, or re-render from blocks on read? | Store it. Simple-mode chapters need the column regardless; chapter read is the hottest page and rendering costs a Purifier pass per view; and pinning the HTML at save protects anchoring from future sanitiser or markup changes. Desync is prevented by a single writer. |

## 8. Out of scope

- **Any data migration of existing chapters.** Nothing is converted in bulk, now or later, without a new decision.
- `author_note` — stays simple rich-text.
- Per-block `[data-annotable]` regions. The pre-loop architecture anticipated
  them; decision 3 makes them unnecessary and they would break the single-root
  constraint.
- Image annotation and image quoting.
- Warning or notifying anyone about stale quotes; any server-side re-anchoring.
- Story cover images. They bypass the Media domain entirely and are not a GC
  concern — a separate matter if ever.
- Static pages (`multiedit-static-pages/`) and the `narrative` toolbar preset.
- Search indexing of chapter content.
- Block-level moderation or reporting.

## 9. Open questions

1. **Are chapters soft-deleted?** — *non-blocking*. If they are, the usage
   provider must report soft-deleted chapters' image paths too, or restoring a
   chapter yields dead images. DESIGN must check and state which.
2. **How are words counted from text blocks?** — *non-blocking*. `EditorPublicApi`
   exposes `plainTextLength()` (characters, text blocks only) but no word
   equivalent. DESIGN decides whether to extend the Editor's public API or count
   in Story. §4.6 fixes the required behaviour either way.
3. **Does `prose rich-content [text-indent:2rem]` still apply to paragraphs
   nested inside `.ce-block--text`?** — *non-blocking*, but it is a VERIFY
   checklist item with teeth: a change in paragraph indentation or spacing is
   both a visual regression and an anchoring risk.
