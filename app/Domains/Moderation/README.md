# Moderation module

The moderation modules aims at:
- enabling users to report sensitive content
- enabling moderators and admins to act_upon sensitive content

An admin panel also exists to manage reports.

The module is a generic module, it can be used to report any type of content. Each module is in charge of registering its own signals and giving actions to moderators/admins.

## Technical detail
### Overview
- Topics (reportable entity types) are registered at boot time (no DB table) into a central registry.
- Users submit reports via a reusable Blade component. Reasons are configured in Admin (Filament).
- Moderators review reports in Admin. Moderation does not take content actions; each domain handles its own actions.

### Registering a topic (from another domain)
- In your domain service provider `boot()`, register a topic into the `ModerationRegistry`:
  - Provide a unique `key` (e.g. `story`, `chapter`, `comment`, `profile`).
  - Provide a translatable display name key (e.g. `story::moderation.topic_name`).
  - Optionally provide a `SnapshotFormatter` class.

What the registry provides:
- Lookup of active reasons per topic.
- Validation that an optional formatter implements the expected interface.

### Snapshot formatter (optional but recommended)
- Purpose: capture a JSON snapshot of the reported content and render it for moderators even if the source changes.
- Interface responsibilities:
  - Capture a domain-specific JSON snapshot at report time.
  - Render the snapshot for the Admin detail view.
  - Return the reported user ID (`getReportedUserId`) for attribution.

### Using the report button in other domains
- Include the reusable component where reporting is needed:
  - `<x-moderation::report-button :topic="'story'" :entity-id="$story->id" />`
- The component loads reasons for the topic and posts an Ajax/JSON report with:
  - reporter, topic key, entity id, reason, optional comment, snapshot (if formatter), URL, timestamp.

### Admin (Filament) resources
- Reasons management: create/sort/activate reasons per topic.
- Reports queue: list, filter (topic, entity, reporter, reported user, status, dates), review details.
- Review actions are tracked (approve/reject) with optional internal notes. No automated domain actions.

### Integration checklist for a domain
- Register your topic in `boot()` with key + display name (+ optional formatter).
- Add the report button to your UI where appropriate.
- If needed, implement a formatter to snapshot and render your content and expose `getReportedUserId()`.
- Provide your own moderator/admin tools to act on content; Moderation does not enforce content actions.
