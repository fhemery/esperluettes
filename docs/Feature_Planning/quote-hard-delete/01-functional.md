# Quote — reader quote deletion must be a hard delete — functional specification

> REFINE output (auto mode: no interview). Every statement here is either from
> `00-request.md`, from the orchestrator's relayed user guidance, or a stated
> assumption recorded in [`DECISIONS.md`](./DECISIONS.md).

## 1. Overview

When a reader deletes one of their own quotes (chapter panel or profile
« Citations » tab), the quote must be gone for good. Today it is only
soft-deleted, so a later deactivation → reactivation of that reader brings it
back. Bugfix; no visible UI change.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Suppression par le lecteur | The owner deletes their own quote. Permanent. |
| Quote masquée | A quote hidden because its owner's account is deactivated. Restored on reactivation. |

## 3. Roles & visibility

Unchanged. Only the owner may delete a quote (`QuotePolicy::canEditOrDelete`).

## 4. Functional requirements

### 4.1 Reader deletes a quote

1. The owner deletes one of their quotes.
2. The quote is permanently removed; nothing can bring it back.
3. A non-owner still gets 403 and the quote is untouched.

### 4.2 Deactivation / reactivation (unchanged behaviour, corrected scope)

1. Deactivating a user hides all their live quotes.
2. Reactivating the user restores exactly the quotes hidden by that
   deactivation — never a quote the reader deleted themselves.

### 4.3 Existing data

Quotes soft-deleted before this fix are all reader deletions (there are no
deactivated users in production — user guidance). They are purged once, so a
future reactivation cannot resurrect them (assumption A2).

## 5. Lifecycle

| Event | Effect on the user's quotes |
|-------|-----------------------------|
| Reader deletes a quote | That row is hard-deleted. |
| User deactivated | All live rows soft-deleted (unchanged). |
| User reactivated | All soft-deleted rows restored — these can now only be deactivation-hidden rows. |
| User deleted | All rows hard-deleted, soft-deleted included (unchanged). |

Contest entries (Calendar › Quote Contest) snapshot the quote and never
dereference `quote_id`, so a hard-deleted source quote leaves them intact.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — unchanged, owner-only delete. |
| Visibility / privacy | N/A — a deleted quote was already invisible. |
| Settings | N/A |
| Notifications | N/A — deletion notifies nobody (unchanged). |
| Domain events | N/A here — `admin-statistics-quotes/` adds a deletion event later. |
| Statistics | No metric exists yet; `admin-statistics-quotes/` builds on this fix. |
| Moderation | N/A |
| Lifecycle / cascade | See §5. |
| Media | N/A |
| Search | N/A |
| i18n | N/A — no string changes. |
| Mobile | N/A — no UI change. |
| Accessibility | N/A — no UI change. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| — | Reader deletion | Hard delete (`00-request.md`). |
| — | Deactivation | Keeps soft-deleting; reactivation restores only those (`00-request.md`). |

## 8. Out of scope

- Moderator access to the admin statistics page.
- Emitting a quote-deletion domain event (`admin-statistics-quotes/` phase 2).
- Any UI change (no "undo", no confirmation change).

## 9. Open questions

None blocking.
