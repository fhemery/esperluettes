# Chapter annotations — v3 (in-chapter display) — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-04 | REFINE | Where do annotation avatars go? | Quote's 24px gutter at `md`+, same layout `md` and `lg`; no inline icon; filter menu at the top of the article | vision #15 |
| 2 | 2026-10-04 | REFINE | Author's heat map vs annotation overlay | Heat on hides annotation tint, avatars and filter menu; heat off restores them | — |
| 3 | 2026-10-04 | REFINE | Reader's quote tint vs annotation tint | Both shown, distinct styles; quote tint stays clickable; avatar opens the annotation | — |
| 4 | 2026-10-04 | REFINE | Moderators' in-chapter display | None — pop-up only | vision §3/§7 (moderator in-chapter view) |
| 5 | 2026-10-04 | REFINE | Moderator « Vider le contenu » on one annotation | Body → « <Annotation supprimée> »; anchor, tint, replies kept; commenter may delete, no longer edit | — |
| 6 | 2026-10-04 | REFINE | Commenter's own popover | Full: « Modifier » / « Supprimer » (draft or pending change), « Répondre » per v2 rule; pending-deleted annotations hidden from text | — |
| 7 | 2026-10-04 | REFINE | Replay of the whole flow + assumptions A1–A10 | Approved as written | — |
| 8 | 2026-10-04 | DESIGN | How is the annotation tint drawn? | CSS Custom Highlight API; ranges rebuilt after Quote renders; Quote untouched | — |
| 9 | 2026-10-04 | DESIGN | How do in-chapter annotations reach the page? | Lazy chapter-level `GET in-entity` endpoint, fetched only by authors and commenters with a root | — |
| 10 | 2026-10-04 | DESIGN | Where do avatars live / how they yield to the heat | Comment's own gutter overlay in Quote's column, hidden while `quoteAggregate.visible` | — |
| 11 | 2026-10-04 | DESIGN | How is « Vider le contenu » recorded? | Overwrite body + nullable `content_emptied_at` column | — |
| 12 | 2026-10-04 | PLAN | Moderator who annotated a chapter they don't author — in-chapter display? | Own only: treated as a commenter (follows `canAnnotate`). #4 only rules out a moderation view of everyone's annotations | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Annotation tint is a coloured underline (not a background) | REFINE | yes |
| A2 | « +N » = number of *other* root annotations in the group | REFINE | yes |
| A3 | `moved` anchors are tinted on their new passage with no extra marker | REFINE | yes |
| A4 | Filter menu is author-only and shown only when the chapter has a visible annotation | REFINE | yes |
| A5 | Processed annotations, when shown, are dimmed | REFINE | yes |
| A6 | Deleted (anonymised) commenter: default avatar + anonymised name in-chapter and in the filter | REFINE | yes |
| A7 | Tapping the tint below `md` does nothing | REFINE | yes |
| A8 | « Vider le contenu » on root annotations only; replies keep « Supprimer »; processed flag kept | REFINE | yes |
| A9 | No notification, event or audit entry for « Vider le contenu » | REFINE | yes |
| A10 | Avatars / arrows keyboard-focusable with labels, same bar as Quote's pills | REFINE | yes |
| A11 | Non-moderators on `POST /comments/annotations/{id}/empty-content` get the `role` middleware's redirect to the dashboard (as `moderatorDelete`), not the 403 the plan named; the middleware is unchanged | BUILD phase 4 | yes |
| A12 | 422 on a reply is keyed `annotation` with the new message `errors.reply_not_emptiable` (mirrors `reply_not_processable`) | BUILD phase 4 | yes |
| A13 | `in-entity` lists are ordered by root comment id (oldest root first); roots are fetched once and the commenter's own-annotation restriction is applied in memory on one batched root-annotation query. Entity type is validated `required|string|max:64` only — an unknown type answers `lists: []` (plan Open item #2) | BUILD phase 5 | yes |
| A14 | In the pop-up, the commenter's « Supprimer » shows on `can_edit || is_emptied` (server `can_edit` is false once emptied, decision #5 keeps delete); « Modifier » is hidden on `is_emptied` even with a pending edit. After « Vider le contenu » the row is merged with the server DTO, keeping its client-side `missing` flag | BUILD phase 6 | yes |
| A15 | The overlay entry is pushed with `@pushOnce('head-scripts', …)`, not `@push('scripts')` as the plan wrote: `scripts` is emitted after `app.js`, whose `Alpine.start()` would fire `alpine:init` before `Alpine.data('annotationOverlay')` is registered (Quote and the annotations bundle use `head-scripts` for the same reason). The `<x-comment::annotation-overlay>` tag is an explicit `Blade::component` alias, since the namespace lookup would expect a class named `AnnotationOverlay` | BUILD phase 8 | yes |
| A16 | The two `Alpine.effect`s (heat mirror, render) are created in the `annotationOverlay` component's `init()`, not in `alpine:init`: it runs after every `alpine:init` handler, so Quote's stores exist whatever the bundle order. The tint is the theme's info-fg blue (light `rgb(29 78 216)`, dark `rgb(147 197 253)`), 2px underline, processed at 40% alpha, written as literals (custom properties do not reliably reach `::highlight()`); dark mode keys on `:root[data-appearance=dark]`, the Shared theme switch (plan Open item #4). A highlight with no range is deleted from `CSS.highlights` rather than set empty | BUILD phase 9 | yes |
| A17 | A commenter's local (draft / pending-add) rows carry no profile, so the overlay component reads the commenter's own public profile (`ProfilePublicApi::getPublicProfile`, commenters only) into `data-user-name` / `data-user-avatar` for their gutter avatar. The avatar label's `:count` is the « +N » (other visible roots): « Annotation de :name », « … et 1 autre », « … et :count autres ». Avatars are top-aligned with the passage's first line rect; a range with no client rect counts as not visible. `renderHighlights()` now also returns each placement's canonical `start` (for the reading-order tie-break) | BUILD phase 10 | yes |
| A18 | `groupByPosition` groups now carry `members` (every visible item of the band, reading order) and the avatar dispatches `annotation-overlay:open { groupKey, keys }`; the popover keeps those keys and pages through the ones still in `visibleRows` (so a regroup after a processed toggle does not lose its place). The processed toggle reuses the existing `server_modal.mark_processed` / `mark_unprocessed` keys rather than duplicating them at top level; the dialog label reuses `server_modal.title`, the error `server_modal.action_error`. The dispatched `annotations:list-refreshed` list carries `viewer_role` = the overlay store's role. The panel opens to the right of the avatar, else left / below / above (as `<x-shared::popover>`), and takes focus on open; a click on another gutter avatar does not count as an outside click. « Répondre » is shown on any row with `can_reply` (commenters included; phase 12 owns the rest of the commenter's actions); the post-reply hint is author-only, as in the pop-up | BUILD phase 11 | yes |
| A19 | Popover « Modifier » closes the popover and dispatches the capture-form event on the next task (as the drafts modal does). A pending add is edited through the same `annotation:open-edit {tempId}` as a draft — the capture form already resolves it in pending mode, so no new `capture-form.js` helper (plan Open item #5). Deleting a saved row with replies asks the pop-up's `delete_with_replies_confirm` first. Labels reuse `server_modal.edit` and `drafts_modal.delete` | BUILD phase 12 | yes |
| A20 | The filter menu is shown when the author has any row at all (processed or filtered-out included), so unticking every commenter never removes the way back. Anonymised commenters (`author_id` null) share one entry named with the existing `comment::comments.unknown_user` (« Esperluette disparue ») and the default avatar — the server sends them an empty `display_name`. It is a small in-place panel (button « Filtrer les annotations » + checkboxes), not `<x-shared::dropdown>`, which closes on any inner click. The gutter avatar is dimmed (`opacity-50`) when its group's lead row is processed | BUILD phase 13 | yes |
| A21 | Gutter re-layout after VERIFY: Alpine's `x-show` shows one `requestAnimationFrame` late, after the render effect's own frame, so the overlay's `ResizeObserver` now also watches the gutter (it spans the whole root, so it resizes when the filter menu appears and when it leaves `display:none` after the heat map) rather than adding another frame or `$nextTick`. The « Esperluette disparue » fallback lives in one helper (`in-chapter/annotator.js`) used by the gutter label, the filter menu and the popover — reply authors included, which the VERIFY report did not name | VERIFY fix | yes |
