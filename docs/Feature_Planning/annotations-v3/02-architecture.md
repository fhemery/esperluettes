# Chapter annotations — v3 (in-chapter display) — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Prior records: [`_done/annotations.md`](../_done/annotations.md) (v1),
  [`_done/annotations-v2.md`](../_done/annotations-v2.md) (v2)

## 1. Domain placement

**Comment** owns everything, as in v1/v2: data, read endpoint, moderator
action, the in-chapter overlay (Blade component + JS). Story only hosts the
overlay component around its article. Quote is **not modified** (§7 #1, #3):
the annotation overlay coexists with Quote's renderers instead of sharing code
with them.

### 1.1 Changes in other domains

- **Story** — `chapters/show.blade.php` wraps `<article data-quote-article>` in
  `<x-comment::annotation-overlay>` (inside `<x-quote::author-heat>`, so both
  gutters share the article's right edge). Passes the values `ChapterController`
  already computes (`rootCommentId`, `annotationMode`). No controller query
  added. Direct use of a Comment Blade component — edge `StoryPrivate →
  CommentPublic` already exists.
- **Shared** — `Resources/js/anchoring/`: one new pure helper turning canonical
  offsets into a DOM `Range` (§4.2). Quote may later reuse it; not touched now.
- **Quote** — none. Comment's JS *reads* `Alpine.store('quoteAggregate')?.visible`
  and `Alpine.store('quotes')?.items` at runtime (§4.4). This is a documented
  runtime contract, not a PHP dependency; no deptrac edge. Quote's `AGENTS.md`
  gets one line saying those two store names are observed by Comment.
- **config/purifier.php** — the custom-emoji class list is defined once at the
  top of the file and reused by the five profiles (§4.10 of the spec). No
  behaviour change.

## 2. Data model

### 2.1 Tables

One migration on `comment_annotations`
(`YYYY_MM_DD_HHiiss_add_content_emptied_at_to_comment_annotations`):

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `content_emptied_at` | `timestamp` | yes | set by moderator « Vider le contenu »; null otherwise |

No index: never queried on, only read with the row. `down()` drops the column.

### 2.2 Model

`CommentAnnotation` gains `content_emptied_at` in `#[Fillable]` and a
`'content_emptied_at' => 'datetime'` cast, plus `isEmptied(): bool`.

### 2.3 Lifecycle rules

Unchanged from v1/v2 (root soft-delete, user nullify, deactivation via root
comment). New:

- **Emptying** (root annotation only): `body` ← `e(__('comment::annotations.emptied_body'))`
  (« <Annotation supprimée> »), `content_emptied_at` ← now. Anchor fields,
  `is_processed`/`processed_at`, replies untouched. Emptying an emptied row is a
  no-op (timestamp kept). No event, no notification, no audit (A9).
- **After emptying**: the commenter may still delete it (pending delete);
  an edit is refused as `edits.<id>` → `stale` by `saveChanges`.

## 3. PHP architecture

### 3.1 Public API

`AnnotationPublicApi`:

```php
/** One list per root comment of the entity on which the viewer has an
 *  in-chapter role: 'commenter' (own root) or 'author'. Moderator-only and
 *  no-role roots are omitted — never a 403. Empty array for a viewer with
 *  nothing to see or a type without supportsAnnotations(). */
public function getForEntity(string $entityType, int $entityId, int $viewerId): array; // AnnotationListDto[]

/** Root annotation only (422 on a reply). Idempotent. Returns the refreshed row
 *  as seen by a moderator. Role check stays on the route, as moderatorDelete. */
public function moderatorEmpty(int $annotationId, int $byUserId): AnnotationDto;
```

`getForComment` and `getForEntity` share one builder that takes preloaded
collections, so `getForEntity` runs a **constant number of queries** whatever
the number of roots: roots of the entity, root annotations, replies, profiles
(`ProfilePublicApi::getPublicProfiles`, one call), active reply writers
(`AuthPublicApi::getUsersById`, one call — resolves v2's "one Auth call per
pop-up open" note for the in-chapter path), author check
(`CommentPolicyRegistry::canMarkAsProcessed`, once per entity).

Role per root uses the existing precedence (`resolveViewerRole`: commenter →
author → moderator), then drops `moderator` and `null`. The author check is
evaluated once for the entity rather than once per root.

`AnnotationDto` gains `isEmptied` (`is_emptied`). `canEdit` is false when
emptied. `isProcessed` stays `null` for the commenter role — the in-entity
payload goes through the same DTO, so the processed flag cannot leak (tested
server-side, spec §6).

### 3.2 Services

- `AnnotationService`: `getRootsForComments(int[] $commentIds, ?int $authorId)`
  (batched sibling of `getRootsForComment`), `emptyContent(CommentAnnotation)`.
  `applyChanges` treats an edit on an emptied row as stale (same path as a
  foreign/deleted id).
- `CommentService`: root comment ids of an entity (visible roots only — trashed
  roots and their already soft-deleted annotations are excluded by the existing
  scopes).
- `AnnotationAccessService`: `canEdit` derivation includes "not emptied".

### 3.3 Policy / authorization

| Action | Enforcement |
|--------|-------------|
| `GET` in-entity | `auth` + `compliant` route group; content filtered by role in the API (§3.1). A beta reader, other reader or moderator-not-author gets `[]`. |
| Moderator empty | route group `role:moderator,admin,tech-admin` (same as `moderatorDelete`); API refuses replies (422). |
| Edit an emptied annotation | `saveChanges` → 422 `edits.<id>` = `stale`. |
| Reply / processed / delete from the popover | existing v2 endpoints and checks, unchanged. |

### 3.4 Events and listeners

None emitted, none added (spec §6, A9). Existing listeners unchanged.

### 3.5 Routes, controllers, form requests

| Verb | URI | Controller | Request |
|------|-----|-----------|---------|
| `GET` | `/comments/annotations/in-entity?entity_type=&entity_id=` | `AnnotationController@inEntity` | `AnnotationsInEntityRequest` (`entity_type` registered in `CommentPolicyRegistry`, `entity_id` int) |
| `POST` | `/comments/annotations/{annotationId}/empty-content` | `AnnotationModerationController@empty` | — |

`POST` mirrors the root comment's `POST /comments/{id}/empty-content`. No `PATCH`.
The `in-entity` route must be declared before any `/{commentId}/…` pattern that
could shadow it.

Response of `in-entity`: `{ "lists": [AnnotationListDto::toArray(), …] }`.

## 4. Frontend architecture

All new JS under `Comment/Resources/js/annotations/in-chapter/`, loaded by the
overlay component's own `@vite` push (only when the overlay is active).

### 4.1 Blade: `<x-comment::annotation-overlay>`

Class component `Public/View/Components/AnnotationOverlayComponent` (sibling of
`CommentListComponent`). Props: `entity-type`, `entity-id`, `root-comment-id`,
`annotation-mode`. It computes `inChapterRole` from `CommentPolicyRegistry`:

- `author` when `canMarkAsProcessed(type, id, viewer)`;
- `commenter` when `canAnnotate(type, id, viewer)`;
- otherwise (guest, moderator-not-author, unsupported type) it renders **only
  its slot**, no script — same pattern as `<x-quote::author-heat>`.

Active markup: a `relative` wrapper around the slot, a gutter
(`hidden md:block absolute inset-y-0 right-0 w-6 translate-x-full`, identical
geometry to Quote's), the filter menu (author, `md`+, top of the article), and
the popover panel (teleported to `body`). Data attributes carry the in-entity
URL, role, mode, root id and the lang strings the JS needs.

### 4.2 Rendering: CSS Custom Highlight API (§7 #1)

- Canonical text = `buildCanonicalText(article, { within: '.ce-block--text' })`;
  each row → `findAnchor` → `ok`/`moved` give offsets, `missing` is dropped.
- New Shared helper `rangeFromCanonical(canonical, start, end): Range | null`
  maps offsets through `nodeMap` to one `Range` (may span text nodes; no DOM
  mutation, so no right-to-left concern).
- Two named highlights: `annotation` and `annotation-processed`, styled in the
  Comment CSS with `::highlight(...)` — coloured underline (A1) using a theme
  token with a dark-mode value; processed = same underline at reduced opacity
  (A5). Overlap with `mark.quote-tint` is native: background from Quote,
  underline from us; the quote mark stays clickable (#3).
- Feature-detect `CSS.highlights`: absent → no tint; avatars still work (they
  position from `Range.getBoundingClientRect()`).
- **Range invalidation**: Quote's renderers unwrap/`normalize()`/re-wrap, which
  collapses live ranges. The renderer rebuilds all ranges (cheap: one canonical
  build + N `findAnchor`) after any change to `quotes.items`,
  `quoteAggregate.visible`, the annotation rows, or the filter — deferred one
  animation frame so Quote's own effect has run first.

### 4.3 State: one Alpine store `annotationOverlay`

`{ role, rows, filters: { hiddenCommenterIds: Set, showProcessed: false },
heatOn, visibleRows (getter) }`. `rows` merges:

- server lists from `in-entity` (fetched once on init when role = `author`, or
  `commenter` with a root comment — §7 #2);
- local state from `window.commentDrafts` — drafts slot (`draft` mode) or
  `annotationChanges` (`pending` mode: edits applied, adds appended, pending
  deletes hidden — spec §4.3.1), re-read on `comment-drafts:annotations-changed`
  and `comment-drafts:annotation-changes-changed`.

Filter state lives in the store only: per visit (spec §4.5.4). The pop-up is
never filtered.

### 4.4 Heat coexistence (§7 #3)

`heatOn` mirrors `Alpine.store('quoteAggregate')?.visible` through an
`Alpine.effect`. While true: highlights cleared, gutter and filter menu hidden,
popover closed. Back to false: rebuild in the previous filter state. Commenters
never have the store (authors cannot quote their own story), so the read
returns `undefined` and the overlay is always on.

### 4.5 Gutter and grouping

Pure function `groupByPosition(items, minGap = 20)` (vitest): items sorted by
`top` then canonical start; one group per ~20px band; lead = first visible
item in reading order; badge `+N` = other root annotations in the group (A2).
Avatar `top` from the range's first client rect relative to the gutter.
Recomputed on store change, `resize`, article `ResizeObserver`, image `load` —
the same triggers Quote uses. Avatars are `<button>`s with an `aria-label`
naming the commenter and count (A10).

### 4.6 Popover

Comment-owned Alpine component (the Shared `<x-shared::popover>` cannot be
opened programmatically nor anchored to an arbitrary element). It registers in
`Alpine.store('popover').openId` so it stays exclusive with every other
popover; positioning re-uses the same placement/clamp approach. Content per
annotation: author profile, body (placeholder when emptied), replies, actions
by `can_*` flags. « 1 / N » + arrows when grouped. Escape closes and returns
focus to the avatar.

Actions reuse existing pieces — no duplicated business logic:

| Action | Reuses |
|--------|--------|
| Commenter « Modifier » | `annotation:open-edit {tempId}` (draft) / `annotations:edit-saved-row` (published) — the existing capture form |
| Commenter « Supprimer » | `commentDrafts.removeAnnotation` / `setPendingDelete` |
| « Répondre » | `api.postReply` + `replies.appendReply`; own editor id (not the modal's) |
| Author processed toggle | `api.setProcessed` |

After any server-side mutation the popover dispatches the existing
`annotations:list-refreshed {commentId, list}` so the « N annotations » pop-up
cache stays in sync; the overlay store listens to the same event (the changes
banner already emits it after a save).

### 4.7 Pop-up additions

- `annotationsModal` re-anchors its rows against the article
  (`closest('[data-quote-article]')`, as `capture-form.js` already does) when
  it opens; `missing` rows get the « Passage absent du chapitre » badge.
- Moderator « Vider le contenu » on root rows → `api.emptyAnnotation(id)`; the
  row is replaced by the returned DTO.

### 4.8 Lang

`comment::annotations` gains: `emptied_body`, `empty_content`,
`empty_content_confirm`, `passage_missing`, `mark_processed`,
`mark_unprocessed`, `show_processed`, `filter.*`, `overlay.*` (avatar label,
`+N`, `1 / N`, arrows).

## 5. Deptrac

**No new edge.** `StoryPrivate → CommentPublic` (component use) and
`CommentPublic/Private → Shared, AuthPublic` (profiles, users) already exist.
The Comment → Quote coupling is JS-runtime only (store names), documented in
both domains' `AGENTS.md`.

## 6. Testing strategy

**PHP integration** (`Comment/Tests/Feature/Annotations/`):

- `in-entity`: author sees every visible root's annotations with
  `is_processed`; commenter sees own only, `is_processed` absent/null (no-leak
  test); beta reader, other reader, moderator-not-author, guest → `[]` / 401;
  trashed root excluded; deactivated reply writer filtered; anonymised
  commenter present; unsupported type → `[]`; **query count constant** for 1 vs
  5 roots.
- `empty-content`: moderator only (403 otherwise); reply → 422; body replaced,
  `is_emptied` true, anchor/replies/processed kept; idempotent; subsequent
  `saveChanges` edit → `stale`, delete still accepted; `can_edit` false.
- Overlay component render: active markup for author / commenter, slot only for
  guest, moderator, beta reader, news.
- Purifier: existing `CommentBodySanitizerTest` + one assertion per profile that
  a custom emoji survives.

**Vitest** beside each module: `rangeFromCanonical`, `groupByPosition`, store
merge (drafts / pending / filters / processed / heat), popover action wiring,
modal `missing` badge.

**VERIFY only** (browser): Highlight API painting and dark mode, coexistence
with the quote tint, heat on/off, avatar positions after resize and image load,
keyboard navigation, below-`md` tint-only.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | How is the annotation tint drawn? | **A** CSS Custom Highlight API, ranges rebuilt after Quote renders · **B** one shared `<mark>` coordinator for quote tint, heat and annotations | A | Tint is not clickable (#3), the only thing the API can't do; Quote untouched; overlaps free. Flip if older Firefox (<140) / Safari (<17.2) must be supported or the tint must become clickable. |
| 2 | How do in-chapter annotations reach the page? | **A** new lazy `GET in-entity` endpoint, fetched only by authors and commenters with a root · **B** JSON embedded server-side in the chapter page | A | Chapter page is the hottest page; B adds annotation/profile/Auth queries to every author/commenter render. Flip if the post-load pop-in is unacceptable in VERIFY. |
| 3 | Where do avatars live, and how do they yield to the heat? | **A** Comment's own gutter overlay in the same column, hidden while `quoteAggregate.visible` · **B** gutter lifted out of Quote into a shared host | A | #2 makes the overlays mutually exclusive, so one container buys nothing; no Quote refactor, no deptrac edge. Flip if a third gutter consumer appears or overlays must coexist. |
| 4 | How is « Vider le contenu » recorded? | **A** overwrite body + nullable `content_emptied_at` · **B** overwrite body only, detect by comparing to the placeholder (as root comments) | A | The commenter's edit lock (#5) needs a reliable signal, not a string match that breaks on rewording. |
| 5 | (decided in DESIGN) Pop-up ↔ in-chapter consistency | shared JS store refactor · existing `annotations:list-refreshed` event | event | Reuses v2's channel; no refactor of `modal.js` cache. |
| 6 | (decided in DESIGN) Popover component | `<x-shared::popover>` · Comment-owned component | Comment-owned | Shared popover has no programmatic open and is hover-first; exclusivity kept via `popover.openId`. |

## 8. File layout

```
app/Domains/Comment/
├── Database/Migrations/YYYY_MM_DD_HHiiss_add_content_emptied_at_to_comment_annotations.php
├── Private/Requests/AnnotationsInEntityRequest.php
├── Private/Resources/views/components/annotation-overlay.blade.php
├── Public/View/Components/AnnotationOverlayComponent.php
├── Resources/js/annotations/in-chapter/
│   ├── index.js          (Vite entry: store + components)
│   ├── store.js
│   ├── renderer.js       (Highlight API)
│   ├── gutter.js         (groupByPosition + positioning)
│   ├── popover.js
│   └── filter-menu.js    (+ *.test.js beside each)
└── Tests/Feature/Annotations/{AnnotationsInEntityTest,EmptyAnnotationContentTest,AnnotationOverlayComponentTest}.php
app/Domains/Shared/Resources/js/anchoring/canonical-range.js (+ test)
```

## 9. Risks acknowledged

- **Live-range invalidation by Quote's re-wrap.** Mitigated by rebuild-after-
  frame (§4.2). Revisit (→ §7 #1 B) if VERIFY shows a stale or missing tint
  after quoting/unquoting.
- **Store-name coupling with Quote** (`quoteAggregate`, `quotes`). A rename in
  Quote silently disables heat suppression. Documented in both `AGENTS.md`;
  revisit if Quote's stores move.
- **Script order**: the `Alpine.effect` reading `quoteAggregate` must run after
  Quote registers it (Quote pushes to `head-scripts`, Comment to `scripts`).
  If not, the effect is created inside `alpine:init` after both bundles load.
- **Browser support**: no tint on browsers without `CSS.highlights` (avatars
  remain). Acceptable by #1.
- **Duplicate triples** resolve to `missing`/first match as `findAnchor` does
  today — out of scope (spec §8).
