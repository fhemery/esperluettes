# Fanart — agent notes

- README: [app/Domains/Fanart/README.md](README.md)

## Public API

None yet.

## Events emitted

None yet.

## Listens to

None yet.

## Registrations

- **Media**: `FanartServiceProvider::boot()` registers `FanartMediaUsageProvider` on `MediaUsageRegistry`.

## Invariants

- **Never delete a fanart file from this domain.** Clear rows; `media:gc` collects files.
- **The usage provider must read `withTrashed()`.** Soft-deleted rows (deactivated users) still own their files; dropping that makes GC delete files a reactivation would need.
- **No FK to `users`, `stories` or `chapters`** — cross-domain by rule. Resolve references through `StoryPublicApi`.
- **`SoftDeletes` on `Fanart` is for user deactivation only.** Every other removal (decline, withdraw, moderator or author delete, chapter/story/user deletion) is a hard delete.
- **Route middleware** follows the Quote precedent: `['web', 'auth', 'compliant', 'role:user-confirmed,moderator,admin,tech-admin']`. Never `Route::patch`.
