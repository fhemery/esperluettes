# Quotable blocks opt in — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Quote anchoring stops reading every piece of text in a chapter and reads only
the areas that **opt into** being quotable. In Avancé chapters, text blocks
are quotable while image blocks (captions included) and future non-text blocks
such as the chapter-choice block are not. Simple-mode chapters behave exactly as
today. This pays back a debt contracted when multi-edit landed and unblocks
`multiedit-chapter-switch-block/` (its decision #12).

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Zone citable (quote zone) | The chapter's content area, where the quote system hooks in. One per chapter page. |
| Zone citable / non citable (quotable area) | An area inside the quote zone that declares `quotable = true` or `false`. **An area that declares nothing is not quotable.** |
| Texte canonique (canonical text) | The text that capture, re-anchoring, highlights, the author heat map and the author summary all read. From now on it is the concatenation of the quotable areas only. |

## 3. Roles & visibility

Unchanged. Whoever could quote before can still quote; whoever saw highlights,
the heat map or the summary still sees them. The feature narrows *which text*
can be quoted, not *who* can quote it.

| Role | Can see | Can do |
|------|---------|--------|
| Guest | unchanged | unchanged |
| `user` (non-confirmed) | unchanged | unchanged |
| `user-confirmed` | unchanged | quotes only quotable areas |
| Author / co-author of the target | heat map & summary computed on quotable areas only | unchanged |
| Moderator | unchanged | unchanged |
| Admin | unchanged | unchanged |

## 4. Functional requirements

### 4.1 Which text is quotable

1. The chapter's content area is the quote zone.
2. Inside the quote zone, each area declares whether it is quotable. The default
   (nothing declared) is **not quotable**.
3. **Simple mode** (no blocks): the whole content is a single quotable area.
   Behaviour is identical to today.
4. **Avancé mode**: the block type alone decides quotability. There is no
   per-block author toggle and nothing new is stored per block.
   - text block → quotable;
   - image block, **caption included** → not quotable;
   - any future block type (e.g. chapter-choice) → not quotable unless it
     explicitly opts in.
5. The quotable flag is **applied when the chapter is displayed**, not read
   from the stored chapter content. Chapters already saved get the new
   behaviour without being re-saved or backfilled.

### 4.2 Reader captures a quote

1. The reader selects text inside a single quotable text block. « Citer » works
   as today.
2. The selection touches a non-quotable area, partly or wholly (e.g. an image
   caption). **« Citer » does not appear** in the selection toolbar. The
   toolbar's other actions, if any, are unaffected.
3. The selection spans two quotable text blocks. Unchanged: « Citer » appears
   and the mini-form shows the existing « plusieurs blocs » error.

### 4.3 Reading existing quotes

1. Re-anchoring, the reader's highlights, the author heat map and the author
   summary panel all use the same canonical text, made of quotable areas only.
2. An existing quote whose text, 5-word prefix or 5-word suffix ran through an
   image caption may no longer re-anchor. It then shows up through the existing
   stale-passage handling. This risk is **accepted**, with no audit.

## 5. Lifecycle

No new data. Chapter edit, conversion between Simple and Avancé, deletion and
user deactivation behave as today. Quotability is re-derived on every display,
so it always follows the chapter's current content.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Unchanged (§3). |
| Visibility / privacy | N/A — no data changes visibility. |
| Settings | N/A — no preference; quotability is fixed by block type. |
| Notifications | N/A — nothing new to notify. |
| Domain events | N/A — no new action. |
| Statistics | N/A — word/character counts are not affected (they are computed separately). |
| Moderation | N/A — no reportable content added. |
| Lifecycle / cascade | N/A — no new data (§5). |
| Media | Image blocks and their captions become non-quotable; nothing else changes for images. |
| Search | N/A. |
| i18n | No new user-facing string: the non-quotable case hides a button and shows no message. |
| Mobile | Same behaviour with touch selection. |
| Accessibility | A hidden button is absent, not disabled, so there is no new aria concern. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | How is quotability modelled? | Two levels. The chapter content area is the quote zone (the hook). Each area inside declares quotable true/false, and the default is false. |
| 2 | Where does the flag come from? | It is applied at display time, not read from the stored record. There is no backfill of existing chapters. |
| 3 | Block type or per-block author toggle? | Fixed by block type: text quotable, image/choice not. No author UI. |
| 4 | Existing quotes crossing a caption? | Accept they may go stale. No audit command. |
| 5 | Selection touching a non-quotable area? | « Citer » is hidden in the selection toolbar. |
| 6 | Align the cross-block rule with #5? | No. Cross-block keeps its existing error message. |

## 8. Out of scope

- The chapter-choice block itself (`multiedit-chapter-switch-block/`).
- A per-block "quotable" toggle for authors.
- An audit or repair of existing quotes that ran through a caption.
- Changing the cross-block error behaviour.
- Quoting images or captions.
- Per-block comment annotations (`annotations/` backlog). The overlap on
  per-block anchoring should be kept in mind, but that task is not built here.

## 9. Open questions

None blocking. Parked for DESIGN (technical, not functional):

- **Non-blocking.** How to inject the flag at display time: from the stored
  block wrapper class (`ce-block--text`) or from `content_blocks`. Where the
  hook lives, so that both display paths agree.
- **Non-blocking.** Capture (`mini-form.js`) reads canonical text from
  `.annotable-region`, while render/heat/summary read `[data-quote-article]`.
  Both must converge on the quote zone.
- **Non-blocking.** Hiding « Citer » means the generic Comment selection toolbar
  must learn per-button applicability. The extension point for that, and the
  deptrac edge, belong to DESIGN.
- **Non-blocking.** `block-elements.js`'s block predicate (`div.ce-block`) must
  agree with the quotable-area notion.
