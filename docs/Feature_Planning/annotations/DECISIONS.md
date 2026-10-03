# Chapter annotations — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

Decisions taken before the loop existed (June 2026) live in `01-functional.md`
§11 and `02-architecture.md` §7 rows 1–5; they are not repeated here.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-03 | DESIGN | Selections spanning two chapter blocks (Advanced chapters) | Refuse at capture, like quotes (same `closestBlock` guard and inline error). Multi-paragraph inside one block stays allowed. | spec §4.1 "may span multiple paragraphs / blocks" |
| 2 | 2026-10-03 | DESIGN | Annotatable areas in v1 | Text blocks only (`.ce-block--text`, same selector as Quote); images, captions, chapter-choice excluded | — |
| 3 | 2026-10-03 | DESIGN | Per-annotation Report (spec/arch contradicted each other) | Deferred to vNext; reports target the root comment. No `chapter-annotation` moderation topic in v1 | spec §11 #20, arch rev. 1 decision #10 |
| 4 | 2026-10-03 | DESIGN | Moderator empties the root comment | Its annotations are soft-deleted too | arch rev. 1 §2.3 "keep as-is" |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Drafts live in the comment-draft store's reserved `annotations` slot (one key with the root draft) rather than separate keys | DESIGN | Yes, client-only |
| A2 | Annotation editor = new Editor preset `inline` (bold, italic, custom-emoji) | DESIGN | Yes |
| A3 | User deletion nullifies annotation `author_id` (mirrors comments, not Quote's hard delete) | DESIGN | Yes, one listener |
| A4 | v1 does not fetch annotations on chapter load; the pop-up fetches per comment on open | DESIGN | Yes |
| A5 | A reader who already posted their root comment cannot annotate in v1 (« Annoter » hidden) — direct consequence of no post-publish add | DESIGN | Becomes false with roadmap B |
