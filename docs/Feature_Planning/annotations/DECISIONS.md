# Chapter annotations — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

Decisions taken before the loop existed (June 2026) live in `01-functional.md`
§10 (full list: `annotations-v2/vision-spec.md` §11) and `02-architecture.md`
§7 rows 1–5; they are not repeated here.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-03 | DESIGN | Selections spanning two chapter blocks (Advanced chapters) | Refuse at capture, like quotes (same `closestBlock` guard and inline error). Multi-paragraph inside one block stays allowed. | spec §4.1 "may span multiple paragraphs / blocks" |
| 2 | 2026-10-03 | DESIGN | Annotatable areas in v1 | Text blocks only (`.ce-block--text`, same selector as Quote); images, captions, chapter-choice excluded | — |
| 3 | 2026-10-03 | DESIGN | Per-annotation Report (spec/arch contradicted each other) | Deferred to vNext; reports target the root comment. No `chapter-annotation` moderation topic in v1 | spec §11 #20, arch rev. 1 decision #10 |
| 4 | 2026-10-03 | DESIGN | Moderator empties the root comment | Its annotations are soft-deleted too | arch rev. 1 §2.3 "keep as-is" |
| 5 | 2026-10-03 | PLAN | Deactivation/reactivation vs moderator soft-deleted annotations | Leave annotation rows alone on deactivate/reactivate; the root comment's soft-delete hides them, so moderator removals never come back | arch rev. 2 §2.3 deactivation/reactivation cascade |

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
| A6 | The `(comment_id, parent_annotation_id, deleted_at)` index is named `comment_annotations_tree_index`: Laravel's generated name is 68 chars, over MySQL's 64 limit (SQLite tests do not catch it) | BUILD phase 1 | Yes, rename only |
| A7 | `CommentController::store` now flashes old input (`->withInput()`) when `CommentPublicApi::create` throws — required by "keeps the old body"; applies to every API-side refusal (body too short, not allowed…), not only annotations. Annotation checks run **before** the root checks so a refused annotator gets the error on `annotations`, not `body` | BUILD phase 4 | Yes, one line |
| A8 | `setProcessed` authorizes through `AnnotationAccessService` (role must resolve to `author`), not the raw policy call — so an author who is also the commenter cannot toggle, matching the GET payload's `can_mark_as_processed`. Auth check runs before the reply-row 422 so non-authors learn nothing. `moderatorDelete` has no in-API role check: the route's `role` middleware is the gate (same as `CommentService::deleteByModeration`); `$byUserId` is unused in v1 | BUILD phase 6 | Yes |
| A9 | `AnnotationService::nullifyAuthor` also clears `author_id` on soft-deleted annotation rows (`withTrashed`), unlike `CommentRepository::nullifyAuthor` which only touches live comments — no trace of a deleted user is kept in annotations. Moderator delete of a root comment relies on the force-delete + FK cascade (plan), not the soft-delete §2.3 describes | BUILD phase 8 | Yes, one call |
