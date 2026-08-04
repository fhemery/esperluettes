# News comments — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-04 | REFINE | Moderation reasons: share with chapter comments, or extend Moderation for distinct reasons? | Share the existing `'comment'` topic and reason list — no Moderation change | — |
| 2 | 2026-08-04 | REFINE | Thread depth: one level (current Comment capability) or deeper nesting? | One level (root + replies), same as chapters | — |
| 3 | 2026-08-04 | REFINE | Reply notification fan-out | Root author + prior repliers on the thread, excluding the replier — same as chapter comments | — |
| 4 | 2026-08-04 | REFINE | What happens to comments when the article is deleted? | Cascade-deleted with the article | — |
| 5 | 2026-08-04 | REFINE | Does the 20-char minimum apply to replies? | No — root comments only, replies unrestricted | — |
| 6 | 2026-08-04 | REFINE | Settings grouping for the new notification | Own "comments" group under News, separate from the existing "article published" group | — |
| 7 | 2026-08-04 | REFINE | One root comment per user per article, like chapters? | No cap — unlimited root comments per user per article | — |
| 8 | 2026-08-04 | DESIGN | Comment thread load mode on the article page | Lazy (`page="0"`, `perPage="5"`), same as chapters — no extra query on initial render | — |
| 9 | 2026-08-04 | DESIGN | Comment thread on draft preview (admin-only unpublished article) | Hidden — `NewsCommentPolicy::canCreateRoot()` requires `published`, and `show.blade.php` only renders the comment component when `status === 'published'` | — |
| 10 | 2026-08-04 | PLAN | Functional §3 said guests can read the thread, but `CommentPublicApi::checkAccess()` blocks all logged-out access (read included) identically for every `commentable_type`, and the architecture makes no changes inside Comment. Reconcile spec vs. actual shared behavior? | Accept the existing chapter-comments behavior — guests see a members-only login prompt instead of the thread. `01-functional.md` §3 updated to match. No Comment domain change. | — |
| 11 | 2026-08-04 | PLAN | `Notification/AGENTS.md` said "do not call `registerGroup()` from outside the Notification domain," but the architecture registers `news-comments` locally in `NewsServiceProvider`, mirroring Story's existing `'publication'` group which already breaks that stated rule. Follow the architecture, or move registration into Notification? | Follow the architecture — register `news-comments` locally in `NewsServiceProvider`. `Notification/AGENTS.md`'s invariant corrected instead: a group owned by a single domain and holding only that domain's own types is registered locally; only cross-cutting groups belong in `NotificationServiceProvider`. The reverse (Notification knowing about domain-specific groups) is the real coupling violation. | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| | | | |
