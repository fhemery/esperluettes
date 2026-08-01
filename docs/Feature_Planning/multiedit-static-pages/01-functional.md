# MultiEdit — advanced mode for static pages — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Static pages get the same Simple / Avancé body editor News already has, so an
admin can interleave text and images in long-form admin content. Existing pages
stay in Simple mode until switched. Separately, the StaticPage and News admin
create/edit forms reorder fields so the header image sits earlier in the form.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Simple (`Simple`) | One rich-text body. `content_blocks` is null. Default for new and existing static pages. |
| Advanced (`Avancé`) | Ordered list of text and image blocks stored in `content_blocks`. |
| Block (`bloc`) | One unit of body content — `text` or `image`. |
| Rendered content | HTML stored in `content` and printed on the public page. Author HTML in Simple; derived from blocks in Advanced. |
| Header image | The page/article banner image (`header_image`), distinct from body image blocks. |

User-facing toggle and block strings already live in `editor::multi.*` (French).
This feature adds no new reader-facing vocabulary.

## 3. Roles & visibility

Unchanged. Only `admin` and `tech-admin` edit static pages and news. Public
readers still see published (and admin-preview draft) pages as today.

| Role | Can see | Can do |
|------|---------|--------|
| Guest / `user` / `user-confirmed` / Moderator | Published page content as today | Nothing new |
| Admin / tech-admin | Admin forms + draft preview as today | Use Simple/Avancé on static pages; same News editing as today with the new field order |

Readers see no mode chrome. Advanced and Simple pages both render as HTML body
plus optional header image.

## 4. Functional requirements

### 4.1 Static page — Simple mode (default)

1. Admin opens create or edit.
2. Body field is MultiEdit in **Simple**, editorial toolbar — same formatting
   options as today's rich-text field.
3. Save stores HTML in `content` and leaves `content_blocks` null.
4. Public page output is unchanged for pages that never switch mode.

New pages start in Simple.

### 4.2 Static page — Advanced mode

1. Admin switches to **Avancé**. Existing body HTML becomes one text block.
2. Admin may add/reorder/delete text and image blocks; upload or reuse images
   under the same Media scope already used for static-page headers
   (`static-pages`); alt text required on every image block; captions behave as
   on News.
3. On save, blocks are stored, body images are resolved, text is sanitised as
   News does, and `content` is rewritten from the blocks.
4. Public view still prints `{!! $content !!}` (plus header image as today).

### 4.3 Returning to Simple

Same gate as News: only when there is exactly one text block and no images.
Otherwise the Simple control stays disabled with the existing French tooltip.
On a successful switch and save, `content_blocks` becomes null and `content`
keeps that single text.

### 4.4 Mode memory

Remembered per page the News way: non-null `content_blocks` ⇒ Advanced on next
edit; null ⇒ Simple. No separate preference.

### 4.5 Media usage for GC

Every image path the page owns must remain visible to Media GC: the header path
**and** every Advanced body image path. Under-reporting would let GC delete a
live image.

### 4.6 Admin form field order (StaticPage and News)

Create and edit forms for **both** domains use this order:

1. Title  
2. Slug  
3. Header image  
4. Summary  
5. Body (Simple or Advanced editor)

No other layout or behaviour change on News beyond this reorder. StaticPage
drops the empty standalone “Media” section once the header sits in that order.

## 5. Lifecycle

| Event | Behaviour |
|-------|-----------|
| Page/article deleted | As today; usage provider stops yielding its paths → Media GC may reclaim after grace. |
| Unpublished / republished | Unchanged; mode and blocks travel with the row. |
| Author user deleted | Unchanged (`created_by` nullified); content kept. |
| Existing rows before this feature | Stay Simple (`content_blocks` null); no backfill. |

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Unchanged — `admin` / `tech-admin` only. Non-confirmed vs confirmed N/A. |
| Visibility / privacy | Unchanged publish/draft and public show. |
| Settings | N/A |
| Notifications | N/A — no new notify on edit/mode switch. |
| Domain events | N/A — no new events; existing Updated/Published/etc. unchanged. |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | §5; mirror News for blocks + GC via usage provider. |
| Media | Scope `static-pages`; extend usage to body block paths; News usage already covers blocks. |
| Search | N/A — Search does not index StaticPage. |
| i18n | French; reuse `editor::multi` strings; no new reader copy. |
| Mobile | Same admin MultiEdit behaviour as News. |
| Accessibility | Inherit MultiEdit / image-field behaviour; alt required on body images. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Literal News mirror vs stricter Phase 5a validation? | Literal News mirror. |
| 2 | Header placement; apply to News? | Header above body; News included. |
| 3 | Exact field order? | title → slug → header image → summary → body on both. |
| 4 | Final scope? | Only: MultiEdit on StaticPage body + form reorder on StaticPage and News. Nothing else. |

## 8. Out of scope

- Chapters, FAQ, or any other surface adopting MultiEdit.
- Stricter validation than News (path-in-scope checks, summed-text min/max).
- Changing public StaticPage/News show layout or summary visibility on the public page.
- New events, notifications, settings, search indexing, or content length limits.
- Data migration / backfill of existing pages into Advanced.
- Behaviour changes to News beyond field reorder (News MultiEdit stays as-is).

## 9. Open questions

None.
