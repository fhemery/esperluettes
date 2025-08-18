# Improve Domains: Move Public Name to Profile, Add ProfilePublicApi, Decouple Story

## Context
- Story show and index need to render author names, profile slugs, and avatars with links to profiles.
- Current code introduced:
  - `User->profile()` in `app/Domains/Auth/Models/User.php` creating `Auth -> Profile` dependency.
  - Eager-load chain in `StoryService::getStoryForShow()` that loads `authors.profile`.

## Current Issues
- __Auth ↔ Profile cycle__
  - `Auth` now imports `Profile` at `app/Domains/Auth/Models/User.php`.
  - `Profile` already imports `User` for `Profile::user()`.
  - This violates domain boundaries and makes refactors brittle.

- __Cross-domain eager-load from Story__
  - `app/Domains/Story/Services/StoryService.php::getStoryForShow()` eager-loads `authors.profile:user_id,slug`.
  - Story becomes coupled to Profile’s schema; any change in Profile can break Story.
  - Scaling this to lists (24 stories/page) amplifies the coupling and risks.

## Goals
- Remove cyclic dependency between Auth and Profile.
- Make Profile the authority for public-facing name (display_name) and avatar.
- Avoid cross-domain joins from Story.
- Provide efficient single and batched access to public profile info (display_name, slug, avatar_url) for up to 24+ authors per page.
- Align with our Event-Driven Enhanced MVC architecture (events for write-sync, contract for read).

## Options Considered
- __A) Events first, then shared contract (Recommended)__
  - Move public name authority to Profile using Auth-emitted events (no Auth->Profile imports).
  - Then expose a `ProfilePublicApi` contract returning `ProfileDto` (display_name, slug, avatar_url) for single and batch reads.
  - Pros: Clean write path ownership in Profile; clean read dependency via interface; scalable with caching.

- __B) Public redirect route by user ID__
  - Add `/u/{user}` in Profile (public) that resolves and redirects to `/profile/{profile:slug}`.
  - Story links to `/u/{id}`; no extra queries for links.
  - Pros: Zero extra reads in Story for links. Cons: Separate policy decision; still need API for names/avatars.

- __C) Read model / denormalized projection__
  - Maintain `public_author_profiles(user_id, profile_slug, display_name, avatar_url)` updated via Profile domain events.
  - Pros: Strong isolation and performance at scale. Cons: More setup (events, migration, backfill). Consider later.

- __D) Keep cross-domain eager-loads and `User->profile()` (Not recommended)__
  - Works but breaks boundaries and introduces a cycle; risky as usage grows.

## Decision
- Implement __Events-first migration of public display name to Profile__.
- Registration will __collect display name__; Auth will emit `UserRegistered` including `name` in payload. Profile will initialize its `display_name` from that event.
- Introduce __ProfilePublicApi__ returning __ProfileDto__ (display_name, slug, avatar_url) with single and batch methods and caching.
- Decouple Story from Auth/Profile DB by consuming the new contract for rendering.
- Revisit __Read model__ later if profile usage grows across the app.

## Plan and Phases

### Phase 1: Move public display name authority to Profile (events-first)
- __Schema__: add `display_name` to Profile table (e.g., `profiles.display_name`, nullable initially).
- __Events (Auth → Profile)__: Auth emits `UserRegistered` with `{ user_id, name }` in payload.
- __Listener (Profile domain)__: create profile if missing and set `profiles.display_name` from event payload. No Auth->Profile imports in Auth.
- __Backfill__: one-off command that reads `users.name` and sets `profiles.display_name` for existing profiles (idempotent).
- __UI__: profile edit form becomes the write authority for display name going forward (on edit, emit `ProfileDisplayNameChanged`).
- __Auth domain__: remove `User->profile()` and any `use App\Domains\Profile\...` imports. Remove any Auth-domain user name update flows/events.
 - __Data integrity__: create missing `profile_profiles` rows for any existing users (legacy installs created profiles lazily). Then backfill `display_name`.

### Phase 2: Introduce ProfilePublicApi + ProfileDto
- __Contract__: `app/Domains/Shared/Contracts/ProfilePublicApi.php`
  - `getPublicByUserId(int $userId): ?ProfileDto`
  - `getPublicByUserIds(array $userIds): array<int, ProfileDto>` (keyed by user_id)
- __DTO__: `app/Domains/Shared/DTOs/ProfileDto.php`
  - Fields: `user_id:int`, `display_name:?string`, `slug:?string`, `avatar_url:?string`
- __Implementation__: `app/Domains/Profile/Services/ProfilePublicService.php`
  - Query Profile storage by user_id(s) and map to DTOs.
  - `display_name` comes from Profile; `slug` from Profile; `avatar_url` from Profile (computed or stored).
  - Caching: per-user and per-batch with short TTL (5–15 minutes) and stampede protection.
- __Binding__: `ProfileServiceProvider` binds the contract to the implementation.

### Phase 3: Decouple Story from Auth/Profile DB
- __StoryService__: remove cross-domain eager-loads (no `authors.profile`). Keep `authors:id` only.
- __Lookup__: collect distinct author IDs for show/index, call `ProfilePublicApi::getPublicByUserIds()` once per page.
- __Rendering__: use DTO data for name, slug (for link), and avatar.
- __Fallbacks__: when DTO missing or fields null, render plain text name (temporary: may still use `users.name` until fully migrated) and default avatar; no links without slug.

### Phase 3.5: Defer dropping `users.name`
- Keep `users.name` column for a transition period to ensure all reads are migrated and stable.
- Plan a later migration to drop `users.name` after verifying no remaining references in app and Admin.

### Phase 4 (optional): Public redirect route and/or read model
- __Redirect__: add `/u/{user}` resolving to `/profile/{profile:slug}` for link simplicity, if policy allows.
- __Projection__: if consumption widens, create a read model updated via Profile events and serve reads from it.

## Caching Strategy
- __Driver__: file cache in production (no cache tags).
- __Keys__:
  - Per-user: `profile_public_user:{id}`
  - Per-batch: `profile_public_batch:{md5(sorted_ids)}`
- __TTL__: 5–15 minutes; use `Cache::remember` to prevent stampedes.
- __Invalidation__: on Profile updates that affect slug/display_name/avatar, call `Cache::forget('profile_public_user:{id}')`. Batch keys will naturally refresh on next miss; optionally forget a small set of recently used batch keys if tracked.
- __Chunking__: For large pages, chunk IDs (e.g., 50–100) and merge results.

## Role Reads Decoupling (Profile ↔ Auth)
- Current issue: Profile reads user roles from Auth, creating a cross-domain read.
- __Goal__: Avoid Profile→Auth reads while still enabling role-based UI (e.g., Admin search/sort needs roles and display_name).
- __Options__:
  - (1) Shared contract from Auth (e.g., `AuthPublicApi::getRolesByUserIds(array $ids): array<int, array<string>>`) consumed by Admin (and any domain needing roles). Keeps Profile independent.
  - (2) Projection: maintain a denormalized read model of roles per user updated by Auth role-change events. Higher setup cost, best for heavy usage.
- __Recommendation now__: implement (1) minimal `AuthPublicApi` for role lookups in Admin; keep Profile free of role reads.

## Routing Policy
- If profiles are public, consider adding `/u/{user}` redirect as sugar for links. Otherwise, restrict to authorized contexts only. This is optional and independent of the API.

## Testing
- __Auth/Profile events__:
  - Emitting `UserRegistered`/`UserNameChanged` updates `profiles.display_name`.
  - Backfill command sets `display_name` appropriately.
- __ProfilePublicApi__:
  - Unit tests cover single + batch, caching, and null/missing behavior.
  - Contract tests ensure DTO fields are populated as expected.
- __Story pages__:
  - Feature tests for show/index assert display_name, link when slug exists, avatar rendering, and sensible fallbacks.
  - Ensure no Profile eager-loads are performed from Story.

## Risks and Mitigations
- __Race conditions on cache__: use `remember` and short TTLs; invalidate on updates.
- __Data drift during migration__: backfill and dual-read (Auth.name → Profile.display_name) until all views switch.
- __Coupling creep__: enforce consumption only via `ProfilePublicApi` in Story; forbid imports of Profile in Auth/Story code reviews.
- __Missing profiles__: API returns null/omits user_id; Story renders fallback name and no link.

## Acceptance Criteria
- No direct `Auth -> Profile` dependencies (no imports in `User` model).
- Public display name authority is in Profile, synchronized from Auth via events.
- A shared `ProfilePublicApi` provides single and batched `ProfileDto` with caching.
- Story services do not eager-load Profile relations and do not query Profile/Auth for display fields directly.
- Story uses `ProfileDto` for display_name, slug, and avatar.
- All existing users have a corresponding `profile_profiles` row with a non-null `display_name`.

## Open Questions
- __Table names__: Confirm Profile table name (`profiles` vs `profile_profiles`) and avatar storage/derivation.
- __Access policy__: Are profiles publicly viewable? Should we add `/u/{user}` redirect now or later?
- __Batch limits__: Any upper bound per `getPublicByUserIds()` call (e.g., 100 IDs)?
