# Calendar

This module contains all the features linked to activity planning.

## Overview
The Calendar domain provides a flexible, plugin-based system for managing time-bound activities (contests, writing challenges, collaborative projects). The Calendar core exposes generic listing and rendering, while each activity type contributes its own data model, logic, and UI via a registry.

See `docs/Feature_Planning/Calendar.md` for the full specification.

## Core Concepts
- **Activity**: A record with name, description, image, type, optional role restrictions, and a timeline (preview/start/end/archive dates). State is derived on the fly: Draft → Preview → Active → Ended → Archived.
- **Registry**: `CalendarRegistry` holds activity types and routes rendering to type-specific components.
- **Activity Type Interface**: Each type implements an interface to provide metadata and its main rendering component (`getMainComponent()`).

## Rendering Flow (Generic Components)
- **Dashboard widget** (generic): shows upcoming (Preview), ongoing (Active), and recently finished (Ended) activities with badges and timing hints. Sorting follows the rules in the spec.
- **Activity details page** `/calendar/{slug}` (generic):
  - Loads the Activity and enforces visibility (role restrictions and state rules).
  - Delegates the main body to the activity type’s main component returned by `getMainComponent()` from the registry.

This keeps the Calendar generic while each activity type owns its UI/logic.

## Per-Activity Type Folder Structure
Each activity type is organized under the Calendar domain following our Domain Oriented Architecture. Create a folder per type under `app/Domains/Calendar` with the following sub-structure (use only what you need):

```
app/Domains/Calendar/
  Private/
    Activities/
      <ActivityTypeName>/
        Http/
          Controllers/
        Models/
        Services/
        Resources/
          views/
        Database/
          Migrations/
        Tests/
  Public/
    Api/
      ActivityTypeInterface.php
      CalendarRegistry.php
```

Notes:
- Put activity-specific database tables/migrations under the type folder’s `Database/Migrations`. Use `activity_id` as the FK to the core Activity.
- Keep controllers thin; encapsulate logic in services. Follow Domain Structure rules.
- Views for an activity type live under its `Resources/views` and are referenced by the component returned by `getMainComponent()`.

## Adding a New Activity Type (Checklist)
- Implement `ActivityTypeInterface` and register it in `CalendarRegistry` with a unique key.
- Create `Private/Activities/<ActivityTypeName>/` and add as needed:
  - Models for type-specific data (with `activity_id` FK)
  - Services for business rules
  - Controllers for type pages/endpoints
  - Views for the main component and partials
  - Migrations for type tables
  - Tests covering state/logic
- Ensure `getMainComponent()` returns the Blade component/view name that renders the type’s UI.

## States and Visibility (Recap)
- Draft: admin only
- Preview: visible to eligible users; participation disabled
- Active: visible and participatable (if allowed by type)
- Ended: visible; participation disabled
- Archived: hidden from listings; accessible via direct URL with indicator

## Non-Functional Notes
- Index date fields for state filtering. State is computed dynamically (no cron).
- Enforce role-based access consistently in queries/controllers.
- Keep activity-specific performance concerns within each type (indexes, eager loading).

