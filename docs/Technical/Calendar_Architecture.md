# Calendar Domain - Architecture & Design

## Overview
This document defines the technical architecture for the Calendar domain, focusing on the plugin-based activity system, file organization, and cross-domain integration patterns.

## Domain Structure & File Organization

### Core Principle
The Calendar domain follows a **plugin architecture** where:
- **Calendar Core**: Manages the base Activity model, listing, visibility, and admin interface
- **Activity Types**: Self-contained plugins that implement specific activity logic, each isolated in its own subdirectory

### Complete Directory Structure

```
app/Domains/Calendar/
├── PublicApi/
│   ├── CalendarRegistry.php                    # Registry service (singleton)
│   └── Services/
│       └── ActivityQueryService.php            # Public API for querying activities
│
├── Private/
│   ├── Models/
│   │   └── Activity.php                        # Base activity model
│   │
│   ├── Services/
│   │   ├── ActivityService.php                 # CRUD operations for activities
│   │   └── ActivityStateService.php            # State computation & visibility logic
│   │
│   ├── Controllers/
│   │   ├── CalendarController.php              # Public listing page (/calendar)
│   │   └── ActivityController.php              # Individual activity page (delegates)
│   │
│   ├── Admin/
│   │   └── Resources/
│   │       └── ActivityResource.php            # Filament admin panel
│   │
│   ├── Activities/                             # ⭐ Activity Types Directory
│   │   │
│   │   ├── JardiNo/                            # ⭐ Example: JardiNo Activity
│   │   │   ├── JardiNoActivityType.php         # Implements ActivityTypeInterface
│   │   │   ├── Controllers/
│   │   │   │   └── JardiNoController.php       # Activity-specific endpoints
│   │   │   ├── Models/
│   │   │   │   ├── JardiNoParticipant.php
│   │   │   │   └── JardiNoGardenItem.php
│   │   │   ├── Services/
│   │   │   │   └── JardiNoService.php          # Business logic
│   │   │   ├── Listeners/
│   │   │   │   └── UpdateJardiNoProgress.php   # Story event listener
│   │   │   ├── Resources/
│   │   │   │   ├── views/
│   │   │   │   │   ├── main.blade.php          # Activity page
│   │   │   │   │   ├── widget.blade.php        # Dashboard widget
│   │   │   │   │   └── components/
│   │   │   │   │       └── [activity components]
│   │   │   │   └── lang/
│   │   │   │       └── fr/
│   │   │   │           └── jardino.php
│   │   │   └── Database/
│   │   │       └── Migrations/
│   │   │           └── [jardino migrations]
│   │   │
│   │   ├── NominationContest/                  # ⭐ Example: Contest Type
│   │   │   └── [similar structure]
│   │   │
│   │   └── DailyChallenge/                     # ⭐ Example: Daily Challenge
│   │       └── [similar structure]
│   │
│   ├── Resources/
│   │   ├── views/
│   │   │   ├── calendar/
│   │   │   │   ├── index.blade.php             # Activity listing page
│   │   │   │   └── show.blade.php              # Activity detail wrapper
│   │   │   └── components/
│   │   │       ├── activity-card.blade.php     # Used in listings
│   │   │       ├── activity-state-badge.blade.php
│   │   │       └── activity-widget.blade.php   # Dashboard widget
│   │   │
│   │   └── lang/
│   │       ├── fr/
│   │       │   └── calendar.php                # Core translations
│   │       └── en/
│   │           └── calendar.php
│   │
│   └── Providers/
│       └── CalendarServiceProvider.php
│
├── Database/
│   └── Migrations/
│       └── 2024_10_20_000000_create_activities_table.php
│
└── Tests/
    ├── Unit/
    │   ├── CalendarRegistryTest.php
    │   └── Activities/
    │       └── JardiNo/
    │           └── [activity-specific tests]
    └── Feature/
        ├── ActivityManagementTest.php
        └── Activities/
            └── JardiNo/
                └── [activity feature tests]
```

### Shared Contracts Location

```
app/Domains/Shared/
└── Contracts/
    └── Calendar/
        └── ActivityTypeInterface.php          # Interface for activity types
```

## File Organization Rationale

### Why Activity Types Live in `Private/Activities/`

**✅ Advantages:**
1. **Complete Isolation**: Each activity type is self-contained
2. **Clear Boundaries**: Easy to see what belongs to which activity
3. **Domain Cohesion**: Activities are Calendar's responsibility, not separate domains
4. **Easy to Find**: All activities in one predictable location
5. **Consistent Structure**: Each activity follows same pattern
6. **Migration Management**: Activity migrations clearly separated

**🚫 Why Not Separate Domains?**
- Activities aren't independent features; they're Calendar plugins
- Reduces domain proliferation
- Simpler dependency management
- Activities share Calendar's core models and services

**🚫 Why Not Flat in Private/?**
- Would mix activity code with calendar core
- Harder to isolate and maintain
- Less clear ownership

### Activity Type Structure Requirements

Each activity type **must** follow this structure:
```
Private/Activities/{ActivityName}/
├── {ActivityName}ActivityType.php      # REQUIRED: Implements interface
├── Controllers/                        # Activity endpoints
├── Models/                             # Activity models
├── Services/                           # Business logic
├── Listeners/                          # Event listeners (optional)
├── Resources/
│   ├── views/
│   │   ├── main.blade.php             # REQUIRED: Main page
│   │   └── widget.blade.php           # REQUIRED: Dashboard widget
│   └── lang/                          # Activity translations
└── Database/
    └── Migrations/                     # Activity migrations
```

## Core Components

### 1. CalendarRegistry (PublicApi)

**Location:** `app/Domains/Calendar/PublicApi/CalendarRegistry.php`

**Purpose:** Central registry managing activity type registration and resolution.

**Interface:**
```php
class CalendarRegistry
{
    // Register an activity type
    public function register(string $typeKey, string $activityTypeClass): void;
    
    // Get all registered types (for admin dropdown)
    public function getAvailableTypes(): array;
    
    // Resolve an activity type instance
    public function resolve(string $typeKey): ActivityTypeInterface;
    
    // Check if type is registered
    public function has(string $typeKey): bool;
}
```

**Registration in CalendarServiceProvider:**
```php
$registry = app(CalendarRegistry::class);

$activityTypes = [
    'jardino' => JardiNoActivityType::class,
    'nomination-contest' => NominationContestActivityType::class,
];

foreach ($activityTypes as $key => $class) {
    $registry->register($key, $class);
}
```

### 2. ActivityTypeInterface (Shared Contract)

**Location:** `app/Domains/Shared/Contracts/Calendar/ActivityTypeInterface.php`

```php
interface ActivityTypeInterface
{
    // Human-readable name for admin dropdown
    public function getTypeName(): string;
    
    // Blade component name for main activity page
    public function getMainComponent(): string;
    
    // Blade component name for dashboard widget
    public function getWidgetComponent(): string;
    
    // Register event listeners for this activity type
    public function registerListeners(EventBus $eventBus): void;
    
    // Check if user can participate (activity-specific logic)
    public function canUserParticipate(User $user, Activity $activity): bool;
    
    // Optional admin configuration fields
    public function getAdminConfigFields(): array;
}
```

### 3. Activity Model

**Location:** `app/Domains/Calendar/Private/Models/Activity.php`

**Key Methods:**
```php
class Activity extends Model
{
    // Accessor for current state
    public function getStateAttribute(): string;
    
    // Scope for visible activities
    public function scopeVisibleTo($query, ?User $user);
    
    // State checks
    public function isInPreview(): bool;
    public function isActive(): bool;
    public function hasEnded(): bool;
    public function isArchived(): bool;
    
    // Get activity type instance
    public function getActivityType(): ActivityTypeInterface;
}
```

### 4. ActivityStateService

**Location:** `app/Domains/Calendar/Private/Services/ActivityStateService.php`

**Purpose:** Centralizes state computation and visibility logic.

```php
class ActivityStateService
{
    // Compute current state from dates
    public function computeState(Activity $activity): string;
    
    // Check visibility
    public function isVisibleTo(Activity $activity, ?User $user): bool;
    
    // Check participation eligibility
    public function canParticipate(Activity $activity, User $user): bool;
    
    // Query for visible activities
    public function visibleActivitiesQuery(?User $user): Builder;
    
    // Get activities for dashboard widget
    public function getWidgetActivities(?User $user): Collection;
}
```

### 5. ActivityService

**Location:** `app/Domains/Calendar/Private/Services/ActivityService.php`

**Purpose:** CRUD operations and business logic.

```php
class ActivityService
{
    public function create(array $data, User $creator): Activity;
    public function update(Activity $activity, array $data): Activity;
    public function delete(Activity $activity): void;
    public function validateDates(array $dates): array;
}
```

## Activity Type Implementation Example

### JardiNoActivityType Class

```php
// app/Domains/Calendar/Private/Activities/JardiNo/JardiNoActivityType.php

class JardiNoActivityType implements ActivityTypeInterface
{
    public function getTypeName(): string
    {
        return 'JardiNo Writing Challenge';
    }
    
    public function getMainComponent(): string
    {
        return 'calendar::activities.jardino.main';
    }
    
    public function getWidgetComponent(): string
    {
        return 'calendar::activities.jardino.widget';
    }
    
    public function registerListeners(EventBus $eventBus): void
    {
        $eventBus->listen(
            ChapterPublished::class,
            UpdateJardiNoProgress::class
        );
    }
    
    public function canUserParticipate(User $user, Activity $activity): bool
    {
        // User must have at least one story
        return $user->stories()->exists();
    }
    
    public function getAdminConfigFields(): array
    {
        return []; // Or return Filament fields
    }
}
```

## Database Schema

### Activities Table

```php
Schema::create('activities', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->longText('description')->nullable();
    $table->string('image_path')->nullable();
    $table->string('activity_type');
    $table->json('role_restrictions');
    $table->boolean('requires_subscription')->default(false);
    $table->integer('max_participants')->nullable();
    $table->timestamp('preview_starts_at')->nullable();
    $table->timestamp('active_starts_at')->nullable();
    $table->timestamp('active_ends_at')->nullable();
    $table->timestamp('archived_at')->nullable();
    $table->foreignId('created_by_user_id')->constrained('users');
    $table->timestamps();
    
    $table->index(['activity_type', 'active_starts_at', 'active_ends_at']);
});
```

### Activity-Specific Tables

Each activity creates its own tables with `activity_id` foreign key:

```php
// JardiNo example
Schema::create('jardino_participants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('story_id')->constrained()->cascadeOnDelete();
    $table->integer('target_words');
    $table->integer('current_word_count')->default(0);
    $table->integer('progress_percentage')->default(0);
    $table->integer('flowers_earned')->default(0);
    $table->timestamps();
    
    $table->unique(['activity_id', 'user_id']);
});
```

## Routes

### Calendar Core Routes

```php
// In CalendarServiceProvider
Route::prefix('calendar')->name('calendar.')->group(function () {
    Route::get('/', [CalendarController::class, 'index'])->name('index');
    Route::get('/{activity:slug}', [ActivityController::class, 'show'])
        ->name('activity.show');
});
```

### Activity-Specific Routes

```php
// JardiNo example
Route::prefix('calendar/jardino')->name('calendar.jardino.')
    ->middleware('auth')->group(function () {
        Route::post('/{activity}/enroll', [JardiNoController::class, 'enroll'])
            ->name('enroll');
        Route::post('/{activity}/plant-flower', [JardiNoController::class, 'plantFlower'])
            ->name('plant-flower');
    });
```

## View Component Delegation

### Activity Controller

```php
public function show(Activity $activity)
{
    // Check visibility
    if (!app(ActivityStateService::class)->isVisibleTo($activity, auth()->user())) {
        abort(404);
    }
    
    // Resolve activity type and get component
    $component = $activity->getActivityType()->getMainComponent();
    
    // Render with dynamic component
    return view('calendar::calendar.show', [
        'activity' => $activity,
        'component' => $component,
    ]);
}
```

### Show View Template

```blade
{{-- calendar/show.blade.php --}}
<x-dynamic-component :component="$component" :activity="$activity" />
```

## Event System

### Events Emitted by Calendar

```php
// State transitions
ActivityEnteredPreview::dispatch($activity);
ActivityStarted::dispatch($activity);
ActivityEnded::dispatch($activity);
ActivityArchived::dispatch($activity);

// Admin actions
ActivityCreated::dispatch($activity, $admin);
ActivityUpdated::dispatch($activity, $admin);
ActivityDeleted::dispatch($activity, $admin);
```

### Events Consumed by Activity Types

Activity types register listeners for domain events:

```php
// In JardiNoActivityType::registerListeners()
$eventBus->listen(ChapterPublished::class, UpdateJardiNoProgress::class);
```

## Summary

### Key Architectural Decisions

1. **Activity types live in `Private/Activities/{ActivityName}/`**
   - Self-contained with full MVC structure
   - Isolated but still part of Calendar domain
   - Easy to add new activities without touching core

2. **Registry pattern for activity type management**
   - Central registration in `PublicApi/CalendarRegistry.php`
   - Interface contract ensures consistency
   - Runtime resolution and delegation

3. **State-derived visibility**
   - No cron jobs or status updates
   - Computed from dates on-the-fly
   - Enforced at query level

4. **Component-based rendering**
   - Activity types provide component names
   - Dynamic component resolution
   - Consistent wrapper with custom content

5. **Event-driven integration**
   - Activity types register their own listeners
   - Loose coupling with other domains
   - No direct service calls

---

**Document Status**: Architecture Design v1.0  
**Last Updated**: 2024-10-20  
**Next Steps**: Implementation phase planning
