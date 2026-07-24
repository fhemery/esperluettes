# MultiEdit — Functional Specification

## 1. Overview

Several forms in the app let an author write a body of content, but only as **a single block of rich text**, through the shared editor (`app/Domains/Shared/Resources/views/components/editor.blade.php`). Today this is true of at least three surfaces:

| Surface | Model / field | Form |
|---------|---------------|------|
| **News** | `News.content` (HTML) | `News/.../admin/news/_form.blade.php` |
| **Static pages** | `StaticPage.content` (HTML) | `StaticPage/.../admin/_form.blade.php` |
| **Chapters** | `Chapter.content` (HTML) | Chapter create/edit form |

Authors have asked to **mix content types** inside one body — most immediately, to insert **images** between paragraphs (illustrations, separators, banners), and later possibly video or other embeds.

**MultiEdit** introduces an opt-in **Advanced mode** on these forms. In Advanced mode, the single editor is replaced by an ordered list of **blocks**, each block being one content type (text or image for now). The author adds, inserts, reorders, and deletes blocks freely. The system stores this structured content, re-displays it in both edit and view modes, and remembers whether a given document was authored in Simple or Advanced mode.

**Simple mode remains the default** and keeps today's exact storage and behavior. Advanced mode is strictly additive: an author who never opts in sees no change.

### v1 scope

**v1 ships Advanced mode on News only.** Static pages and Chapters are explicitly deferred (§10). The mechanism is designed to be shared so later surfaces reuse it, but only News is wired, tested, and released first. Chapter-specific concerns (annotations over text blocks) are documented here so the shared design doesn't paint us into a corner, but they are **not** built in v1.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| **Surface** | A form/page that hosts editable body content: News, Static page, or Chapter. |
| **Simple mode** | The current behavior: one rich-text editor, stored as a single HTML string. The default for every new and existing document. |
| **Advanced mode** | The MultiEdit behavior: an ordered list of typed blocks. |
| **Block** | One unit of content of a single type, in a definite position within the document. v1 block types: **text** and **image**. |
| **Text block** | A rich-text region, rendered with the shared editor using the surface's usual toolbar configuration. |
| **Image block** | A single image, uploaded or picked from an existing library, with mandatory alt text and optional caption. |
| **Block palette** | The row of "add a block" buttons (one per allowed block type) shown at the bottom of the Advanced editor. |
| **Insert affordance** | The dynamic "+" control that appears between two blocks, letting the author insert a new block at that position. |
| **Image library** | The set of previously-uploaded images the author can reuse, scoped by storage path (§5.4). |
| **Reference (usage)** | An occurrence of a stored image path inside a document. One stored file may be used many times — across documents **and repeated within a single document**. |

## 3. Editor Modes

### 3.1 Mode toggle

Each supported form gains a **mode toggle** near the body field: *Simple* (default) / *Advanced*.

- A **new** document opens in Simple mode.
- An **existing** document opens in the mode it was last saved in (§6). The toggle reflects that mode.

### 3.2 Switching Simple → Advanced

Allowed at any time. On switch:

- The current editor's HTML content becomes the **first text block** of the Advanced document.
- The block palette appears at the bottom; the author can start adding image/text blocks.
- No content is lost.

### 3.3 Switching Advanced → Simple

Allowed **only when** the document consists of exactly **one text block and zero image blocks**. In that case, switching back restores the plain single-editor Simple mode with that block's HTML.

If the document has more than one block, or any image block, the *Simple* toggle is **disabled** with a tooltip explaining why (e.g. *"Remove extra blocks and images to return to Simple mode"*). This prevents silent data loss (images and block structure have no representation in Simple mode).

## 4. Advanced Mode Mechanics

### 4.1 Layout

Advanced mode renders the document as a **vertical, ordered stack of blocks**. Below the stack sits the **block palette**: one button per allowed block type for that surface. For v1 that is two buttons: **Add text** and **Add image**. The palette is never removed — clicking a palette button **appends** a new block of that type to the end of the stack.

### 4.2 Adding a block

- **Append**: clicking a palette button adds an empty block of that type at the end.
- **Insert between**: hovering (or focusing, for keyboard/touch) the gap between two blocks — and the gap above the first block and below the last — reveals a **"+" insert affordance**. Activating it opens the same per-type choice and inserts the new block **at that position**, pushing subsequent blocks down.

### 4.3 Reordering blocks

Each block has **move controls** (up / down, and/or drag handle). Reordering changes the block's position in the stored order. Reordering never alters block content.

### 4.4 Deleting a block

Each block has a **delete** control. Deleting removes the block from the document.

- Deleting a **text block** discards its text.
- Deleting an **image block** just stops the document from using that file's path. The file itself is garbage-collected only if nothing else uses it anymore (§5.3). A confirmation is shown only when the block is non-empty.

### 4.5 Empty blocks

A document may transiently contain empty blocks while editing. On **save**, empty text blocks (no non-whitespace, non-image content) and image blocks with no image selected are **dropped** rather than persisted, so authors aren't punished for leaving a stray empty block. (Validation in §7 runs on the surviving blocks.)

## 5. Image Blocks & the Image Library

### 5.1 Uploading a new image

An image block reuses the existing upload component (`app/Domains/Shared/Resources/views/components/image-upload.blade.php`): drag-drop or click to pick a file, client-side preview, size check.

- Images are **uploaded on form submit** (multipart), together with the rest of the document — not via immediate AJAX. All-or-nothing with the save.
- Accepted types and max size follow the component defaults (jpg/png/webp; 2 MB), overridable per surface.
- **No cap** on the number of images per document.

### 5.2 Reusing an existing image

Each image block also offers **"Choose existing"**, opening a **picker** over the author's image library (§5.4). Selecting an image points the block at the already-stored file by its **storage path** — **no re-upload, no duplicate file**. This lets an author reuse, for example, a single custom separator image across many documents without uploading it dozens of times. The **same image may also be used several times within one document** (e.g. the same separator between each section of a chapter).

Each image block also shows, for authors' awareness, **how many places currently use this image** ("used in N places"), so removing it from one spot is an informed choice.

### 5.3 Image lifecycle — usage-driven cleanup

Stored image files are cleaned up based on **whether any content still uses them** — the image's storage path is the record of use, so there is no separate reference bookkeeping to keep in sync:

- Uploading a new image, or picking an existing one, means the block now **uses that path**.
- The same path may be used **many times** (multiple blocks, multiple documents, or repeated within one document).
- Removing an image from a block, or deleting a document, simply means that content **no longer uses the path**.
- A stored file is **garbage-collected** by a scheduled cleanup once **no content uses it anymore** and it has sat unused past a short **grace window** (a safety margin so a file removed and re-added shortly after, or an in-progress edit, is never destroyed prematurely).

Consequence, made explicit for authors' expectations: reuse is durable **as long as the image is used somewhere**. If the author removes it from the very last place it's used, the file is eventually garbage-collected; re-adding it much later means uploading it again. There is **no separate "keep forever" library manager** in v1.

### 5.4 Library scope & storage paths

The reuse picker lists images under the **current surface's storage path**:

| Surface | Storage path | Library sharing |
|---------|--------------|-----------------|
| **Chapters** | `chapters/{userId}/…` | **Per author** — a chapter author sees only their own uploaded chapter images. |
| **News** | `news/…` | **Shared** among News editors/admins. |
| **Static pages** | `static-pages/…` | **Shared** among Static-page editors/admins. |

So the picker is **scoped to its surface**: a chapter's picker shows only that author's chapter images; a News picker shows the shared News image pool. Cross-surface reuse (e.g. reusing a News image inside a chapter) is **not** offered — surfaces have separate pools by path. The storage folder is a **parameter of the shared component**, so each surface declares its own path.

> Cleanup operates within these pools; a file under `news/` is retained while any News document uses it, independent of chapters.

### 5.5 Image metadata

Each image block carries:

- **Alt text — mandatory.** Required for accessibility; save is blocked (with a clear per-block error) if an image block has no alt text. Reusing an existing image pre-fills the alt text previously stored, still editable per block.
- **Caption — optional.** Displayed under the image in view mode when present.

No alignment or width controls in v1: images render **centered and responsive** (`max-width: 100%`), captions beneath.

## 6. Persistence & Mode Memory

- Simple-mode documents are stored **exactly as today** (single HTML string in the existing `content` field). Nothing changes for them.
- Advanced-mode documents store their **ordered list of typed blocks**, from which the Advanced mode is inferred. (The concrete storage shape is an architecture decision; functionally, the requirement is: the system knows the mode and the block order/content on reload. Image blocks reference their file by **storage path**.)
- On reopening a document for editing, the form **restores the exact mode and block structure** it was saved in.
- The mode is a property of the **document**, not a global setting; two News articles can independently be Simple or Advanced.

## 7. Validation

- Character **min/max** limits that a surface enforces today (e.g. a chapter's minimum length) apply in Advanced mode to the **sum of the plain-text content of all text blocks**. Image blocks (and their captions/alt text) do **not** count toward these limits.
- Word count / character count metrics shown to the author aggregate across **text blocks only**.
- Per-block validation: every image block must have a selected image and non-empty alt text.
- Standard input sanitization applies per text block, identical to the single-editor path today.

## 8. Rendering

### 8.1 Edit mode

- Simple: the single editor, as today.
- Advanced: the block stack (§4), each text block being a live editor with the surface's usual toolbar, each image block showing its preview + alt/caption fields + reorder/delete controls, and the palette at the bottom.

### 8.2 View mode (public display)

- Simple: rendered exactly as today (the stored HTML).
- Advanced: blocks rendered **in stored order** — text blocks as sanitized rich HTML, image blocks as centered responsive `<img>` with alt text (and caption beneath if present).
- The rendered output must be consistent between the three surfaces' public views and any place that currently re-displays `content`.

## 9. Chapter-Specific Concerns (deferred, design-forward)

Chapters are **not** in v1, but the shared design must not block them. When Advanced mode later reaches chapters:

- **Annotations still work on text blocks.** A reader's selection for a quote/annotation is **constrained to a single text block** — a selection may not span across an intervening image block. Each text block is its own annotatable region; the canonical plain-text projection used for anchoring (see `Chapter_Annotations.md` §5.4) is built **per text block**, not across the whole document.
- **Word/character counts and minimum-length** validation sum across all text blocks (§7), consistent with how a single-block chapter counts today.
- **Editing that deletes or reorders a text block** interacts with existing annotations via the current `anchor_missing` handling: annotations whose passage no longer resolves are marked missing, exactly as when text is edited today. No new behavior is required beyond building the canonical text per block.
- **Annotating an image** (attaching a comment/reaction to an image block) is **out of scope** and tracked as a future item in `Chapter_Annotations.md` §10.

## 10. Out of Scope (v1) — Reserved for Later

- **Advanced mode on Static pages and Chapters.** v1 is **News only**. The shared mechanism is built to be reused, but the other two surfaces are wired and released later.
- **Block types beyond text and image** (video, embeds, galleries, columns/layout blocks). The palette is designed to grow, but v1 offers text + image only.
- **Image editing** (crop, resize, alignment, width controls). v1 renders centered + responsive.
- **A persistent "keep forever" image library manager / media browser** decoupled from usage. v1 GC-collects files no content uses anymore.
- **Cross-surface image reuse** (using a News image in a chapter, etc.). Pools are separated by storage path.
- **Immediate/AJAX image upload** with server-side draft persistence. v1 uploads on submit (multipart).
- **Annotating image blocks** on chapters (tracked in `Chapter_Annotations.md`).
- **Migration/backfill of existing content into Advanced blocks.** Existing documents stay Simple until an author explicitly opts a document into Advanced.

## 11. Constraints & Decisions Confirmed

| # | Topic | Decision |
|---|-------|----------|
| 1 | v1 surface(s) | **News only.** Static pages + Chapters deferred; shared mechanism designed for reuse. |
| 2 | Default mode | **Simple** — unchanged current storage & behavior. |
| 3 | Simple → Advanced | Always allowed; existing HTML becomes the first text block. |
| 4 | Advanced → Simple | Allowed **only** when exactly one text block and zero image blocks; otherwise toggle disabled with tooltip. |
| 5 | Block types (v1) | Text and image. |
| 6 | Palette | One button per allowed type, at the bottom; appends. Never removed. |
| 7 | Insert between blocks | Dynamic "+" affordance in the gaps; inserts at that position. |
| 8 | Reorder / delete | Per-block move + delete controls. Deleting an image just stops the document using its path (file GC'd only when no content uses it). |
| 9 | Empty blocks | Dropped on save. |
| 10 | Image upload timing | On form submit (multipart), all-or-nothing with the save. |
| 11 | Image count cap | **None.** Same image may be reused, including **repeated within one document**. |
| 12 | Image types / size | Component defaults (jpg/png/webp, 2 MB), overridable per surface. |
| 13 | Image reuse | "Choose existing" picker points the block at an existing **path**; no re-upload/duplicate. Component shows "used in N places". |
| 14 | Image lifecycle | **Usage-driven GC** — a scheduled sweep deletes files no content uses anymore, past a short grace window. No reference table; no keep-forever library. |
| 15 | Library / picker scope | Storage-path scoped: `chapters/{userId}/` (per author), `news/` & `static-pages/` (shared). No cross-surface reuse. |
| 16 | Alt text | **Mandatory** per image block; blocks save on absence. |
| 17 | Caption | Optional; shown under image in view mode. |
| 18 | Image display | Centered, responsive; no alignment/width controls in v1. |
| 19 | Mode memory | Persisted per document; form restores exact mode + block structure on reload. |
| 20 | Min/max & counts | Sum across **text blocks only**; images excluded. |
| 21 | Text block editor | Reuses the surface's existing editor toolbar configuration per block. |
| 22 | Chapter annotations (future) | Selection constrained to a single text block; canonical text built per block; existing `anchor_missing` handling on edits. |

## 12. User Flows (illustrative)

### 12.1 News editor builds an illustrated article (v1)

1. Editor opens the News create form. Body is in **Simple mode** (default), a single editor.
2. Editor writes an intro paragraph, then flips the toggle to **Advanced**. The intro becomes **text block #1**; the palette (*Add text* / *Add image*) appears at the bottom.
3. Editor clicks **Add image** → an image block appears → drag-drops a photo → fills **alt text** (mandatory) and an optional **caption**.
4. Editor clicks **Add text**, writes the next section.
5. Editor realizes the image belongs *after* the second paragraph: uses the block's **move down** control to reorder.
6. Editor hovers the gap between two paragraphs, clicks the **"+"**, inserts another image — this time via **Choose existing**, picking the site's standard News separator from the shared `news/` library (no re-upload; the block just points at the existing path).
7. Editor submits. All new images upload with the form; the document is saved as **Advanced** with its block order. The separator is now used by one more document.
8. Public News page renders the blocks in order: text, image+caption, text, separator image.

### 12.2 Reusing a separator across many articles

1. A separator image was uploaded once for an earlier News article; it lives under `news/`.
2. For every new article, the editor uses **Choose existing** to point at the same file. The "used in N places" count climbs; the file is stored once.
3. Months later the separator is removed from the last article still using it → nothing uses it anymore → the scheduled sweep garbage-collects the file after the grace window.

### 12.3 Trying to return to Simple mode

1. An Advanced News article has 3 text blocks and 2 images. The editor clicks **Simple** — the toggle is **disabled** with a tooltip: content would be lost.
2. The editor deletes the images and merges text down to a single block, then the **Simple** toggle enables; switching restores the single-editor view with that block's HTML.

---

## Next steps

Once this functional spec is locked, the architecture document (`MultiEdit_Architecture.md`, same folder) will cover:

- Storage shape for Advanced documents (dedicated column vs JSON vs side table) and how it coexists with the existing `content` field + the mode flag.
- The shared Blade/Alpine component(s) for the block editor, palette, insert affordance, reorder/delete, and how surfaces configure block types + storage path + editor toolbar.
- Image usage model: how a file's path records use, the usage-provider registry + scheduled GC sweep, and its grace window.
- The image picker (library listing by storage path) and its server endpoints.
- Multipart submit handling: parsing block order + per-block payloads + new-image uploads in one request.
- View-mode rendering pipeline shared across News (v1) and later surfaces.
- Per-surface integration points for News in v1 (form, controller/service, public view).
- Design hooks that keep chapter annotations (per-block canonical text) feasible later.
- Testing strategy (mode round-trip, block CRUD/reorder, usage-driven GC, validation summing, view rendering).

Then a planning document (`MultiEdit_Planning.md`) will sequence the delivery (shared mechanism → News rollout → later surfaces).
