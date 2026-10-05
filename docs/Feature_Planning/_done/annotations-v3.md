# Chapter annotations — v3 (in-chapter display)

**Status:** DONE — 2026-10-05 · **Domain(s):** `Comment` (+ `Story` host, `Shared` helper)

## What it does

Brings annotations into the chapter text. Annotated passages get a blue
underline (CSS Custom Highlight API). From `md` up, annotators' avatars sit in
the 24px right gutter and open a popover: the author reads, replies to and marks
annotations processed; the commenter edits or deletes their own. The author also
gets a filter menu. Moderators get « Vider le contenu » on one root annotation in
the « N annotations » pop-up. The pop-up now badges rows whose passage is gone
(« Passage absent du chapitre »). The purifier's custom-emoji class list is
declared once in `config/purifier.php`.

## Key behaviour

- **Who sees what:** guest → nothing. Author / co-author (`canMarkAsProcessed`) → every published annotation of visible roots. Any other logged-in user (`canAnnotate`) → **their own only** (drafts, pending adds, published). Moderators and beta readers fall into the commenter rule, so they see their own annotations and nobody else's (decision #12). There is no moderation view in the chapter.
- The processed flag never reaches a commenter (`is_processed: null`, the same DTO builder as the pop-up).
- Below `md`: underline only. No avatar, popover or filter menu, and tapping the underline does nothing.
- **Heat map on** (`Alpine.store('quoteAggregate').visible`) → no tint, no gutter, no filter menu, popover closed. **Heat off** → everything comes back with the same filter state.
- The quote tint (`mark.quote-tint`, a background) and the annotation underline overlap natively. The quote mark stays clickable; only the avatar opens an annotation.
- Re-anchoring is client-side on every view (`findAnchor`). `ok` and `moved` are tinted at their found offset with no marker. `missing` is dropped from the text and badged in the pop-up for all roles.
- **Filter** (author only): a commenter checklist plus « Afficher les annotations traitées ». It lives only in the store and resets on reload. It is shown once the author has any row, so unticking everyone never hides the way back. The pop-up is never filtered. Processed rows, when shown, are drawn at 40% alpha with a dimmed avatar.
- **Grouping:** a gutter band runs while *consecutive* passage tops are less than 20px apart (chained, not fixed windows). « +N » counts the other visible roots in the band; the popover pages « 1 / N » through every visible member.
- **« Vider le contenu »** works on roots only (a reply → 422 `annotation`). The body becomes « <Annotation supprimée> » and `content_emptied_at` is set. Anchor, tint, replies and processed flag are kept. The call is idempotent. Afterwards the commenter can still delete but not edit (`can_edit` false; an edit in `saveChanges` → `stale`). It sends no event, notification or audit entry. The pop-up asks with `window.confirm`.
- **Anonymised commenter** (`author_id` null): default avatar and « Esperluette disparue » (`comment::comments.unknown_user`), via `in-chapter/annotator.js`, everywhere. All anonymised commenters share one filter entry.
- **Deactivated** commenters' roots are soft-deleted, so nothing of theirs shows. Deactivated reply writers are filtered at read time (one batched Auth call per `in-entity` request).
- A commenter's local rows have no profile. The component injects the commenter's own name and avatar (`data-user-name` / `data-user-avatar`).

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `Comment/Public/Api/AnnotationPublicApi.php` — `getForEntity`, `moderatorEmpty`, private `buildItems` (shared with `getForComment`) |
| Services | `Comment/Private/Services/AnnotationService.php` (`getRootsForComments`, `emptyContent`, `filterEmptiedIds`), `CommentService::getRootCommentsFor` → `CommentRepository::getRootsByTarget` |
| Routes | `GET /comments/annotations/in-entity` (`AnnotationController@inEntity`, `AnnotationsInEntityRequest`); `POST /comments/annotations/{id}/empty-content` (`AnnotationModerationController@empty`, moderator role group) |
| Blade component | `Comment/Public/View/Components/AnnotationOverlayComponent.php` + `Private/Resources/views/components/annotation-overlay.blade.php`; explicit `Blade::component` alias in `CommentServiceProvider` |
| Host | `Story/Private/Resources/views/chapters/show.blade.php`, which wraps `<article data-quote-article>` inside `<x-quote::author-heat>` |
| JS | `Comment/Resources/js/annotations/in-chapter/` (`index.js` entry, `store.js`, `renderer.js`, `gutter.js`, `popover.js`, `filter-menu.js`, `annotator.js`, `overlay.css`); pop-up changes in `annotations/modal.js`, `api.js` |
| Shared helper | `Shared/Resources/js/anchoring/canonical-range.js` (`rangeFromCanonical`) |
| Vite entry | root `vite.config.js` (one input line) |
| Migration | `Comment/Database/Migrations/2026_10_05_000001_add_content_emptied_at_to_comment_annotations.php` |
| Tests | `Comment/Tests/Feature/Annotations/{AnnotationsInEntityTest,EmptyAnnotationContentTest,AnnotationOverlayComponentTest}.php`, `Story/Tests/Feature/Chapters/ChapterAnnotationOverlayViewTest.php`, `Shared/Tests/Unit/PurifierCustomEmojiProfilesTest.php`; vitest beside each JS module; e2e `e2e/tests/core/chapter-annotation-overlay.spec.ts` (heat map on/off, quote overlap; world = story 6 `ANNOTATED`) |

## Extension points used

- `CommentPolicyRegistry`: `supportsAnnotations` / `canAnnotate` / `canMarkAsProcessed` derive the overlay role. Any annotatable entity can host `<x-comment::annotation-overlay>`.
- `Alpine.store('popover').openId`: the annotation popover is exclusive with Shared popovers.
- The existing `annotations:list-refreshed` event keeps the pop-up cache and the overlay store in sync. The `comment-drafts:*` events feed the commenter's local rows.
- The JS runtime contract with Quote's stores `quotes.items` and `quoteAggregate.visible` is documented in both domains' `AGENTS.md`. It adds no deptrac edge.

## Decisions worth remembering

- **Highlight API, not `<mark>`** (#8): the overlay never touches the DOM, so Quote stays unmodified. Quote's re-wraps collapse live ranges, so the render effect rebuilds every range one frame after any change to `quotes.items` or the visible rows. Flip this if the tint must become clickable or old Firefox/Safari must show it.
- **Lazy `in-entity` fetch** (#9), only for authors and for commenters who have a root. Its query count is constant whatever the number of roots. Nothing is added to the chapter page render.
- **Own gutter, not shared with Quote** (#10). The heat map and the annotation overlay are mutually exclusive (#2).
- **`content_emptied_at` column** (#11), not a placeholder string match. The edit lock depends on it.
- **The bundle is pushed to `head-scripts`, not `scripts`** (A15): `scripts` comes after `app.js`'s `Alpine.start()`, which would be too late for `Alpine.data`. The `Alpine.effect`s are created in the component's `init()` (A16), so Quote's stores exist by then.
- **The gutter is relaid out by a `ResizeObserver` on the article *and* the gutter** (A21). Alpine's `x-show` shows an element one frame late, so a rAF-time layout measures stale positions.

## Where the code departs from the plan and architecture

- Arch §3.2 put the "not emptied" `canEdit` rule in `AnnotationAccessService` and the edit lock in `AnnotationService::applyChanges`. In the code, `canEdit` is computed in `AnnotationPublicApi::buildItems`, and `saveChanges` rejects edits via `filterEmptiedIds`. `AnnotationAccessService` is unchanged.
- Arch §3.5: `entity_type` is validated `required|string|max:64` only. The registry has no "is registered" method, so an unknown type returns `lists: []` (A13). Lists are ordered by root comment id, and roots without annotations are omitted.
- Arch §3.3 / plan expected a 403 for a non-moderator on `empty-content`. The code returns the `role` middleware's redirect to the dashboard, the same as `moderatorDelete` (A11).
- Arch §4.1 / §6 listed moderators and beta readers as getting only the slot. They get the commenter overlay, limited to their own annotations (decision #12).
- Arch §4.8 lang keys: the processed toggle, edit, delete, dialog title and error labels reuse the existing `server_modal.*` / `drafts_modal.*` keys (A18, A19). Only `overlay.*`, `filter.*`, `show_processed`, `emptied_body`, `empty_content(_confirm)`, `passage_missing` and `errors.reply_not_emptiable` are new.
- The filter menu is a small in-place panel, not `<x-shared::dropdown>`, because that component closes on any inner click (A20).
- The tint colours are literals (info-fg blue; dark theme keyed on `:root[data-appearance=dark]`), not theme tokens, because custom properties do not reliably reach `::highlight()` (A16).

## Not done

- **Non-goals (spec §8):** per-annotation Report and image annotation (→ `annotations-v4/`, see `BACKLOG.md`); avatars / popover / tap below `md`; in-chapter display for moderators; heat map and overlay together; a persistent (Settings) filter toggle; manual, server-side or fuzzy re-anchoring and duplicate-triple resolution; the processed flag shown to the commenter; « Vider le contenu » on replies, or restoring an emptied annotation; notifications, events and statistics; using the empty `lg` right column.
- **Cut mid-build:** nothing.
- **Known limits:** no tint on browsers without `CSS.highlights` (avatars still work). The popover's dark-mode legibility was only screenshot-checked, and the user still has to eyeball it.
- **Found along the way:** `<x-shared::button>` danger + outline renders at about 1.2:1 contrast (checkpoint 6v; worked around with solid danger). This belongs to [`shared-button-contrast/`](../shared-button-contrast/00-request.md).
- **e2e:** the 36-test feature spec was retired at archive; only the heat-map and quote-overlap tests were promoted to core, and `e2e/pages/AnnotationOverlay.ts` was trimmed to what they use. The `ANNOTATED` fixture and seeder rows are kept whole.
