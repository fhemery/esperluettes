---
name: migrate-admin-screen
description: Migrate a Filament resource from app/Domains/Admin/Filament/ to a domain-owned custom admin page. Use when working through the Admin_Migration_Roadmap.md checklist.
---

# Migrate a Filament Admin Resource

Converts a Filament resource into a standard Laravel controller + Blade views, registered in the domain's `AdminNavigationRegistry` and tested with Pest.

**Roadmap:** `docs/Feature_Planning/Admin_Migration_Roadmap.md`  
**Reference implementation:** `app/Domains/News/` (controller, views, routes, nav, tests)

---

## Before you start

Read the Filament resource you are migrating:
- `app/Domains/Admin/Filament/Resources/{Domain}/{Resource}Resource.php`
- Its `Pages/` subdirectory (List, Create, Edit, View pages)

Note:
- Which model it uses
- Which domain APIs / services it calls (`*PublicApi`, `*Service`, DTOs)
- Which operations it supports (list / create / edit / delete / custom actions)
- What roles can access it
- Any special behaviour (reordering, image upload, rich editor, custom actions, bulk actions)

Cross-reference the roadmap entry for the resource — it lists cross-domain dependencies and special cases.

---

## Step 1 — Controller

Create `app/Domains/{Domain}/Private/Controllers/Admin/{Resource}Controller.php`.

```php
<?php

namespace App\Domains\{Domain}\Private\Controllers\Admin;

use App\Domains\{Domain}\Private\Models\{Model};
use App\Domains\{Domain}\Private\Requests\Admin\{Resource}Request;
use App\Domains\{Domain}\Private\Services\{Domain}Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class {Resource}Controller extends Controller
{
    public function __construct(
        private readonly {Domain}Service $service,
    ) {}

    public function index(): View
    {
        $items = {Model}::query()->orderBy('...')->paginate(20);

        return view('{domain}::pages.admin.{resource}.index', compact('items'));
    }

    public function create(): View
    {
        return view('{domain}::pages.admin.{resource}.create');
    }

    public function store({Resource}Request $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('{domain}.admin.{resource}s.index')
            ->with('success', __('{domain}::admin.{resource}s.created'));
    }

    public function edit({Model} $model): View
    {
        return view('{domain}::pages.admin.{resource}.edit', compact('model'));
    }

    public function update({Resource}Request $request, {Model} $model): RedirectResponse
    {
        $this->service->update($model, $request->validated());

        return redirect()->route('{domain}.admin.{resource}s.index')
            ->with('success', __('{domain}::admin.{resource}s.updated'));
    }

    public function destroy({Model} $model): RedirectResponse
    {
        $this->service->delete($model);

        return redirect()->route('{domain}.admin.{resource}s.index')
            ->with('success', __('{domain}::admin.{resource}s.deleted'));
    }
}
```

**Rules:**
- Controllers call services or domain Public APIs — never models directly for mutations
- Use Form Request for validation (`app/Domains/{Domain}/Private/Requests/Admin/`)
- Read-only operations (index, show) may query the model directly if no service method exists
- For resources with custom actions (approve, dismiss, set-on/off), add dedicated methods per action

---

## Step 2 — Views

Create views in `app/Domains/{Domain}/Private/Resources/views/pages/admin/{resource}/`.

**Index view skeleton:**

```blade
<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <x-shared::title>{{ __('...') }}</x-shared::title>
            <a href="{{ route('{domain}.admin.{resource}s.create') }}">
                <x-shared::button color="primary">{{ __('...') }}</x-shared::button>
            </a>
        </div>

        <x-shared::flash-block />

        <div class="surface-read p-4 overflow-x-auto">
            <table class="w-full admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('...') }}</th>
                        <th class="p-3 text-right">{{ __('...') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3">{{ $item->name }}</td>
                            <td class="p-3 text-right flex justify-end gap-2">
                                <a href="{{ route('{domain}.admin.{resource}s.edit', $item) }}">
                                    <x-shared::button color="secondary" size="sm">{{ __('Modifier') }}</x-shared::button>
                                </a>
                                <form method="POST" action="{{ route('{domain}.admin.{resource}s.destroy', $item) }}"
                                      onsubmit="return confirm('{{ __('Confirmer la suppression ?') }}')">
                                    @csrf @method('DELETE')
                                    <x-shared::button type="submit" color="danger" size="sm">{{ __('Supprimer') }}</x-shared::button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="p-6 text-center text-muted">{{ __('Aucun élément.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $items->links() }}
    </div>
</x-admin::layout>
```

**Create / Edit view skeleton:**

```blade
<x-admin::layout>
    <div class="flex flex-col gap-6">
        <x-shared::title>{{ __('...') }}</x-shared::title>

        <x-shared::flash-block />

        <form method="POST" action="{{ route('{domain}.admin.{resource}s.store') }}" class="flex flex-col gap-6">
            @csrf

            <div>
                <x-shared::input-label for="name" :required="true">{{ __('Nom') }}</x-shared::input-label>
                <x-shared::text-input id="name" name="name" :value="old('name', $item->name ?? '')" />
                <x-shared::input-error :messages="$errors->get('name')" />
            </div>

            <div class="flex gap-4">
                <x-shared::button type="submit" color="primary">{{ __('Enregistrer') }}</x-shared::button>
                <a href="{{ route('{domain}.admin.{resource}s.index') }}">
                    <x-shared::button type="button" color="secondary">{{ __('Annuler') }}</x-shared::button>
                </a>
            </div>
        </form>
    </div>
</x-admin::layout>
```

**Available shared components:**
- `<x-shared::title>` — page heading
- `<x-shared::button color="primary|secondary|danger" size="sm|md">` — buttons
- `<x-shared::input-label for="..." :required="true">` — form labels
- `<x-shared::text-input id="..." name="..." :value="...">` — text inputs
- `<x-shared::input-error :messages="$errors->get('field')">` — validation errors
- `<x-shared::flash-block />` — session flash messages
- `<x-shared::editor name="content" :value="old(...)">` — rich text editor (add `@push('scripts')` Vite bundle)
- `<x-shared::image-upload name="image" :existing="$item->image_path ?? null">` — image with preview
- `<x-shared::toggle name="is_active" :checked="old('is_active', $item->is_active ?? true)">` — checkbox toggle
- `<x-shared::pagination>` — wraps `$items->links()`

---

## Step 3 — Routes

Add to `app/Domains/{Domain}/Private/routes.php`:

```php
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\{Domain}\Private\Controllers\Admin\{Resource}Controller;

Route::middleware(['web', 'auth', 'role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
    ->prefix('admin/{domain}')
    ->name('{domain}.admin.')
    ->group(function () {
        Route::resource('{resource}s', {Resource}Controller::class)
            ->except(['show']);

        // Add non-resource routes before the resource() call if needed:
        // Route::post('{resource}s/reorder', [{Resource}Controller::class, 'reorder'])->name('{resource}s.reorder');
    });
```

**Route naming convention:** `{domain}.admin.{resource}s.{action}` (index, create, store, edit, update, destroy)

**Roles in middleware string:** use the same roles that the Filament resource's `canAccess()` method checked. Only ADMIN and TECH_ADMIN by default; add MODERATOR if the resource was accessible to moderators.

If the domain's routes.php already has an admin group, add the new resource inside it rather than creating a new group.

---

## Step 4 — Navigation Registration

In `app/Domains/{Domain}/Providers/{Domain}ServiceProvider.php`, add or update `registerAdminNavigation()`:

```php
use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Support\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;

protected function registerAdminNavigation(): void
{
    $registry = app(AdminNavigationRegistry::class);

    // Create the group if it doesn't exist yet:
    $registry->registerGroup('{group_key}', __('Label du groupe'), 50);

    $registry->registerPage(
        '{domain}.admin.{resource}s',
        '{group_key}',
        __('Label de la page'),
        AdminRegistryTarget::route('{domain}.admin.{resource}s.index'),
        '{material_symbol_icon}',    // e.g. 'shield', 'key', 'flag' — Material Symbols name
        [Roles::ADMIN, Roles::TECH_ADMIN],
        10,                          // sort order within group
    );
}
```

Call `$this->registerAdminNavigation()` at the end of `boot()`.

See the roadmap for the exact group key, icon, and role list per resource.

---

## Step 5 — Tests

Create `app/Domains/{Domain}/Tests/Feature/Admin/{Resource}ControllerTest.php`:

```php
<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\{Domain}\Private\Models\{Model};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('{Resource} Admin Controller', function () {

    describe('index', function () {
        it('displays the list for admins', function () {
            $user = admin($this);
            {Model}::factory()->create(['name' => 'Test item']);

            $this->actingAs($user)
                ->get(route('{domain}.admin.{resource}s.index'))
                ->assertOk()
                ->assertSee('Test item');
        });

        it('denies access to non-admins', function () {
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->get(route('{domain}.admin.{resource}s.index'))
                ->assertRedirect(route('dashboard'));
        });

        it('redirects unauthenticated users to login', function () {
            $this->get(route('{domain}.admin.{resource}s.index'))
                ->assertRedirect(route('login'));
        });
    });

    describe('create', function () {
        it('displays the create form', function () {
            $this->actingAs(admin($this))
                ->get(route('{domain}.admin.{resource}s.create'))
                ->assertOk();
        });
    });

    describe('store', function () {
        it('creates a record', function () {
            $this->actingAs(admin($this))
                ->post(route('{domain}.admin.{resource}s.store'), [
                    'name' => 'New item',
                ])
                ->assertRedirect(route('{domain}.admin.{resource}s.index'));

            $this->assertDatabaseHas('{table}', ['name' => 'New item']);
        });

        it('validates required fields', function () {
            $this->actingAs(admin($this))
                ->post(route('{domain}.admin.{resource}s.store'), [])
                ->assertSessionHasErrors(['name']);
        });
    });

    describe('edit', function () {
        it('displays the edit form', function () {
            $item = {Model}::factory()->create(['name' => 'Existing']);

            $this->actingAs(admin($this))
                ->get(route('{domain}.admin.{resource}s.edit', $item))
                ->assertOk()
                ->assertSee('Existing');
        });
    });

    describe('update', function () {
        it('updates a record', function () {
            $item = {Model}::factory()->create(['name' => 'Old']);

            $this->actingAs(admin($this))
                ->put(route('{domain}.admin.{resource}s.update', $item), ['name' => 'New'])
                ->assertRedirect(route('{domain}.admin.{resource}s.index'));

            $this->assertDatabaseHas('{table}', ['id' => $item->id, 'name' => 'New']);
        });
    });

    describe('destroy', function () {
        it('deletes a record', function () {
            $item = {Model}::factory()->create();

            $this->actingAs(admin($this))
                ->delete(route('{domain}.admin.{resource}s.destroy', $item))
                ->assertRedirect(route('{domain}.admin.{resource}s.index'));

            $this->assertDatabaseMissing('{table}', ['id' => $item->id]);
        });
    });
});
```

**Test helpers:**
- `admin($this)` — creates a user with ADMIN role
- `moderator($this)` — creates a user with MODERATOR role
- `alice($this, [], true, [Roles::USER_CONFIRMED])` — creates a confirmed regular user
- `latestEventOf(EventClass::name(), EventClass::class)` — asserts a domain event was fired

**Run tests for the domain:**
```bash
./vendor/bin/sail artisan test app/Domains/{Domain}/Tests/
```

**Run full suite:**
```bash
./vendor/bin/sail artisan test:parallel
```

---

## Step 6 — Remove the Filament Resource

Delete the Filament resource and all its Pages:

```
app/Domains/Admin/Filament/Resources/{Domain}/{Resource}Resource.php
app/Domains/Admin/Filament/Resources/{Domain}/{Resource}Resource/Pages/
```

---

## Step 7 — Add NavigationItem to AdminServiceProvider

After removing the Filament resource, Filament's auto-discovery no longer shows it in the Filament sidebar. Add a hardcoded `NavigationItem` to `AdminServiceProvider::panel()` so both admin systems show the same links (tests verify parity):

```php
// In app/Domains/Admin/Providers/AdminServiceProvider.php
// Inside ->navigationItems([...])

NavigationItem::make('Label')
    ->url('/admin/{domain}/{resource}s')
    ->icon('heroicon-o-{icon}')       // Heroicon name from the roadmap
    ->group('{Filament group label}') // Match the group label used by the Filament resource
    ->sort(N)                         // Match the original sort order
    ->visible(fn (): bool => auth()->user()?->hasRole([Roles::ADMIN, Roles::TECH_ADMIN]) ?? false),
```

**Important:** use a hardcoded URL string, not `route()`. `AdminServiceProvider::panel()` runs during the boot phase before routes are registered — calling `route()` there throws `RouteNotFoundException`. Match the URL to the prefix defined in the domain's `routes.php`. Do not use `__()` for the label either (translations may not be loaded yet); use a plain string.

---

## Step 8 — Deptrac

If the new controller imports from a domain it did not previously depend on, run deptrac and fix any violations:

```bash
./vendor/bin/sail composer deptrac
```

Use the `/fix-deptrac` skill if violations appear.

---

## Step 9 — Verification Checklist

- [ ] `./vendor/bin/sail artisan test app/Domains/{Domain}/Tests/` passes
- [ ] `./vendor/bin/sail artisan test:parallel` passes
- [ ] `./vendor/bin/sail composer deptrac` passes
- [ ] New page is visible in the custom admin sidebar at `/administration/`
- [ ] Same link is visible in the Filament admin sidebar at `/admin/`
- [ ] Authorization works: non-admin users are redirected
- [ ] All CRUD operations work end-to-end
- [ ] Check off all boxes for this resource in `docs/Feature_Planning/Admin_Migration_Roadmap.md`

---

## Special Cases

### Reordering (sort_order)

See `app/Domains/StoryRef/Private/Controllers/Admin/StatusController.php` for the reorder pattern.  
Add a `reorder(Request $request)` method that accepts `['ids' => [1, 2, 3]]` and updates `sort_order` in a transaction. Register a `POST /{resource}s/reorder` route **before** the `Route::resource()` call to avoid conflicts.

### Image Upload

See `app/Domains/News/Private/Controllers/Admin/NewsController.php` and the `<x-shared::image-upload>` component.  
Use `Shared\Services\ImageService` for upload (with responsive variants) and `deleteWithVariants()` for removal.

### Rich Text Editor

Use `<x-shared::editor name="content" :value="old('content', $item->content ?? '')">`.  
Add the editor's Vite bundle via `@push('scripts')` in the view (check how News views do it).

### Delete with validation

Check for usage before deleting and return back with an error:

```php
public function destroy(Model $model): RedirectResponse
{
    if ($model->relatedItems()->exists()) {
        return back()->with('error', __('Cannot delete: item is in use.'));
    }
    $this->service->delete($model);
    return redirect()->route(...)->with('success', __('...'));
}
```

### Slug auto-generation

For forms that include a `slug` field derived from a `name` field, use the shared `slugForm` Alpine component registered in `app/Domains/Shared/Resources/js/app.js`. Do **not** inline the generation logic in `x-data` — it is not maintainable.

On the wrapping `<div>`:
```blade
@php $isEdit = isset($item) && $item->exists; @endphp
<div class="flex flex-col gap-4"
     x-data="slugForm('{{ old('name', $item->name ?? '') }}', '{{ old('slug', $item->slug ?? '') }}', {{ $isEdit ? 'true' : 'false' }})">
```

On the inputs:
```blade
<x-shared::text-input id="name" name="name" x-model="name" @blur="generateSlug()" />
<x-shared::text-input id="slug" name="slug" x-model="slug" @input="slugManuallyEdited = true" class="font-mono" />
```

Behaviour: slug auto-fills from name on blur (create mode only); once the user manually edits the slug field it is locked. After any changes to `slug-utils.js` or `app.js`, run `npm run build`.

---

### Custom workflow actions (approve / dismiss / etc.)

Add named methods on the controller and register as POST routes:

```php
Route::post('{resource}s/{model}/approve', [{Resource}Controller::class, 'approve'])->name('{resource}s.approve');
```

In the view, use a small inline form per action button:

```blade
<form method="POST" action="{{ route('{domain}.admin.{resource}s.approve', $item) }}">
    @csrf
    <x-shared::button type="submit" color="primary" size="sm">{{ __('Approuver') }}</x-shared::button>
</form>
```
