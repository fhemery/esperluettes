# Chapter annotations — v3 (in-chapter display) — request

Split out of [`annotations-v2/`](../annotations-v2/01-functional.md) at its
REFINE (2026-10-03, decision #1). v2 ships the writer side (reactions,
post-publish add/edit/delete, replies); everything still happens in the
« N annotations » pop-up. v3 brings annotations into the chapter.

## What I want

Items from the v2 request's cut list:

| Letter | Theme | Notes |
|--------|-------|-------|
| D | In-chapter display | Client-side re-anchoring (`findAnchor`), passage tint, right-margin avatars with "+N" grouping on `md+`, inline icon on tablet, popover with Reply / processed toggle. `missing` badge in the pop-up. The spec's "click own avatar → edit / delete" flow plugs into v2's pending changes. |
| E | Filter menu | Commenter checklist + « Show processed », right margin only, per session. |
| G | Moderator « Vider le contenu » | On a single annotation. |

Plus the small leftover: the `annotation` purifier profile duplicates the
custom-emoji class list of the other profiles in `config/purifier.php` (a new
emoji must be added twice).

## Constraints or ideas I already have

- The original full spec is `vision-spec.md`, currently in `annotations-v2/`;
  v2's WRAP moves it here. Read v2's record in `_done/` for what v2 changed
  (replies, pending changes, reactions).
- In-chapter display must coexist with Quote's reader tint and author heat:
  each strips its own `<mark>`s, calls `normalize()` and re-wraps; a third
  renderer would invalidate the others' node maps. Coordinate the renderers
  (or move to the CSS Custom Highlight API), and share Quote's right-margin
  gutter rather than adding a second one.
