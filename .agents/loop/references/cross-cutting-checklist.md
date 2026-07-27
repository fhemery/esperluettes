# Cross-cutting concerns checklist

Walk this list during REFINE (functional angle) and again during DESIGN
(technical angle). Most items will be "not applicable" for a given feature —
say so out loud and move on. The point is that nothing is silently skipped.

Ask about **one item at a time**, as a closed question with a recommended
default. Never dump the whole list on the user.

Before asking, check whether the codebase already answers it (spawn a read-only
research agent if that is faster than reading yourself). Ask the user only what
the code cannot tell you.

---

## 1. Who can do it — roles

The role vocabulary: guest · `user` (non-confirmed) · `user-confirmed` ·
moderator · admin. Reference: `app/Domains/Auth`.

- Which roles can see the feature? Which can use it?
- Is a non-confirmed user treated differently from a confirmed one? (This is
  the single most frequently forgotten distinction in this app.)
- Are authors / co-authors of the target entity a special case? (`StoryPublicApi`
  exposes authorship checks.)
- What does a guest see — nothing at all, or a teaser with a login prompt?
- Admin/moderator override: does one exist, and is it audited?

## 2. Visibility & privacy

- Public / private / per-user visibility of the data itself.
- Does it appear on a **profile tab**? Profile tabs register through
  `Profile\Public\Api\ProfileTabRegistry` and carry their own visibility rule —
  the tab must disappear entirely for viewers who may not see it, not render
  empty.
- Is any field strictly private (never leaves the server for non-owners)? If so,
  say it explicitly — it becomes a server-side test, not a Blade `@if`.
- Private-by-default or public-by-default on creation?

## 3. Settings

- Does the user need a toggle or preference? Settings are registered by the
  owning domain through `Settings\Public\Api\SettingsPublicApi` — see the
  `add-setting` skill.
- Default value, and what happens to users who never touch it.
- Does the setting change the meaning of existing data retroactively?

## 4. Notifications

- Does anyone need to be told this happened? Who — the author, the reader, all
  co-authors, moderators?
- Registered via `Notification\Public\Api\NotificationPublicApi`; content types
  are documented in `docs/notification-types.md`.
- Self-notification suppression (don't notify the actor about their own action).
- Batching / flooding: what if the same trigger fires 50 times in an hour?
- Is any private content (a note, a draft) included in the notification body?
  Usually the answer must be no.

## 5. Domain events & audit log

- Should this emit a domain event on `Events\Public\Api\EventPublicApi`? Rule of
  thumb: emit when another domain plausibly cares, or when the action should be
  auditable.
- Which existing events must this feature *listen to*? Almost always:
  user deactivated / reactivated / deleted, and the deletion of the parent
  entity (story, chapter, comment).

## 6. Statistics

- Any counter to increment (`app/Domains/Statistics`, `StatisticDefinition`)?
- Global metric, per-user metric, or both? Shown where?
- Backfill: does the metric need a value for data created before the feature?

## 7. Moderation & reporting

- Can this content be reported? Topics register with
  `Moderation\Public\Api\ModerationRegistry`.
- What can a moderator see and do — hide, delete, warn?
- Is there a private-content tension (moderators needing to see something the
  spec says is strictly private)? Name it; do not resolve it silently.

## 8. Lifecycle, deletion & cascade

- What happens when the parent is soft-deleted? Hard-deleted? Unpublished?
- What happens when the acting user is deactivated / reactivated / deleted —
  nullify the author, soft-delete the row, or cascade?
- Does the feature's data survive the disappearance of what it points at, and
  what does the UI show then (a badge, nothing, a placeholder)?
- Anything to add to a GC sweep (see the `Media` domain's `media:gc` pattern)?

## 9. Content, media & sanitisation

- Rich text or plain text? If rich, which sanitiser applies?
- Images go through `Media\Public\Api\MediaPublicApi` — path-addressed, with a
  usage provider registered so the GC does not delete them.
- Length limits, and what the UI does at the limit.

## 10. Search

- Should this be findable through the global search (`app/Domains/Search`)?
- If yes, what is the indexed text and what does a result row look like?

## 11. i18n

- The app ships **French only** (`Private/Resources/lang/fr/`). Every
  user-visible string goes in a lang file — no literals in Blade.
- Pluralisation and gender agreement in French for any counted string.
- Dates and relative times.

## 12. Architecture boundaries

- Which domain owns this? Creating a new domain is a real decision — argue it.
- Which public APIs are consumed, and does `deptrac.yaml` already allow that
  edge? If not, the edge must be justified before it is added
  (see the `fix-deptrac` skill).
- Does another domain need a new extension point rather than a direct call?

## 13. UI surface

- Which pages change? Is any of them a shared component used elsewhere?
- Mobile (touch selection, small viewport) and tablet behaviour.
- Empty state, loading state, error state.
- Keyboard accessibility and aria labels.
- Does it degrade sanely without JS?

## 14. Performance

- N+1 risk: what needs eager loading?
- Pagination or infinite scroll beyond N items.
- Extra queries added to a hot page (chapter read, home, profile).

## 15. Data & migration

- New table, or columns on an existing one? Indexes for the query patterns.
- Foreign keys only within the owning domain (project rule).
- Is a data migration needed for existing rows?
- `down()` method, always.
