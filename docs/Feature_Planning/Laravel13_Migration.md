# Laravel 13 Migration

## Status

Blocked — requires simultaneous Filament upgrade (see below). Deferred.

Current stack: Laravel 12, Filament 3, PHP 8.4 (Sail), Pest 3.

---

## The Blocker: Filament v3 vs Laravel 13

Filament 3 is fundamentally incompatible with Laravel 13. Filament 3 depends on `illuminate/validation` as a standalone package, which directly conflicts with `laravel/framework ^13.0`. Composer cannot resolve the dependency tree.

The Laravel 13 upgrade therefore requires a **simultaneous Filament upgrade** (v3 → v4 or v5). These are two separate, substantial migration tracks that must be done together.

### Options

| Option | Description | Risk |
|---|---|---|
| **A** | Laravel 13 + Filament 4 | Medium — Filament 4 upgrade guide is substantial |
| **B** | Laravel 13 + Filament 5 | Higher — largest scope, most future-proof |
| **C** | Stay on Laravel 12 for now | None — L12 supported until ~2027 |

---

## Package Version Map (when ready to migrate)

| Package | Current | Target | Notes |
|---|---|---|---|
| `laravel/framework` | `^12.0` | `^13.0` | |
| `laravel/tinker` | `^2.10.1` | `^3.0` | |
| `filament/filament` | `^3.3` | `^4.0` or `^5.0` | Breaking changes — see Filament upgrade guide |
| `pestphp/pest` | `^3.8` | `^4.0` | Required by pest-plugin-laravel v4 |
| `pestphp/pest-plugin-laravel` | `^3.0@dev` | `^4.0` | Stable release, drop `@dev` |
| `laravel/breeze` | `^2.3` | unchanged | Already supports L13 |
| `laravel/pail` | `^1.2.2` | unchanged | Already supports L13 |
| `nunomaduro/collision` | `^8.6` | unchanged | Already supports L13 |

---

## Laravel 13 Breaking Changes (codebase impact)

### Must fix before/during upgrade

**1. CSRF middleware rename**
- File: `app/Domains/Admin/Providers/AdminServiceProvider.php` (lines 16 & 87)
- Change: `VerifyCsrfToken` → `PreventRequestForgery`
- Context: Used in Filament panel middleware stack

**2. Drain queues before deployment**
- File: `app/Domains/Story/Private/Listeners/GrantInitialCreditsOnUserRegistered.php`
- Reason: Laravel 13 changes the job serialization format; jobs queued under L12 will fail on L13 workers

### Safe — no action needed

| Concern | Why safe |
|---|---|
| `->paginate()` default change (15→25) | All 11 call sites pass explicit counts |
| Queue event `$exceptionOccurred` rename | Not used anywhere |
| `Route::controller()` removed | Not used |
| Custom pagination views | Not found |
| Custom cache store implementations | Not found |
| `Model::unguard()` | Not used |
| `Str::slug()` separator arg removed | Not used |
| `bootstrap/app.php` format | Already on modern Laravel 11+ format |
| `Container::call` nullable defaults | No affected patterns found |
| Polymorphic pivot table name change | No `MorphPivot` custom models found |

---

## Eloquent Model Attribute Migration (optional, independent of version upgrade)

Laravel 13 introduces PHP attribute syntax for model configuration. This is **fully optional** — old property-based style still works. Can be done incrementally on any Laravel version ≥ 13.

45 models total. Priority candidates (most properties to migrate):

| Model | File | Properties |
|---|---|---|
| `StoryCollaborator` | `app/Domains/Story/Private/Models/` | `$table`, `$timestamps`, `$incrementing`, `$primaryKey`, `$fillable` |
| `NotificationRead` | `app/Domains/Notification/Private/Models/` | `$table`, `$incrementing`, `$primaryKey`, `$keyType`, `$timestamps`, `$fillable` |
| `Profile` | `app/Domains/Profile/Private/Models/` | `$table`, `$primaryKey`, `$incrementing`, `$keyType`, `$fillable` |
| `Follow` | `app/Domains/Follow/Private/Models/` | `$table`, `$timestamps`, `$fillable` |
| `ReadingProgress` | `app/Domains/Story/Private/Models/` | `$table`, `$timestamps`, `$fillable`, `$casts` |

**Before/after example:**
```php
// Before
class Profile extends Model {
    protected $table = 'profiles';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';
    protected $fillable = [...];
}

// After (Laravel 13+)
#[Table('profiles', key: 'user_id', keyType: 'int', incrementing: false)]
#[Fillable([...])]
class Profile extends Model {}
```

---

## Other Laravel 13 Improvements (not applicable to this codebase)

These features exist in L13 but are either new capabilities (not migrations) or not relevant to this app:

- **Laravel AI SDK** — no AI features in scope
- **Vector search** (`whereVectorSimilarTo`) — no semantic search use case
- **JSON:API resources** — not a JSON:API project
- **`Cache::touch()`** — no TTL-extension use cases
- **`EventStream` responses** — no SSE endpoints
- **Queue routing** (`Queue::route()`) — only 1 queued listener, no benefit
- **`#[Middleware]` / `#[Authorize]` on controllers** — no `$this->middleware()` constructor calls to migrate
- **Job attributes** (`#[Tries]`, `#[Timeout]`) — 1 queued listener with no retry config

### Minor code quality improvement (independent of upgrade)

`json_encode()` in Blade views should be replaced with `Js::from()` for proper XSS-safe encoding:
- `app/Domains/Auth/Private/Resources/views/pages/admin/users/edit.blade.php` (lines 170, 184, 189)

```blade
{{-- Before --}}
{!! json_encode(__('some.key')) !!}

{{-- After --}}
{{ Js::from(__('some.key')) }}
```
