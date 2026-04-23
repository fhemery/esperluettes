---
trigger: always_on
---

# Environment Commands

## Docker/Sail Commands
- Use `sail` instead of direct PHP/Composer commands
- To run all tests : `./vendor/bin/sail artisan test:parallel` (auto-scales to 80% of available cores; override with `TEST_PROCESSES=N`)
- To run selectively tests `./vendor/bin/sail artisan test --filter=<your filter>`
- To run composer : `./vendor/bin/sail composer` 
- To run deptrac if needed : `./vendor/bin/sail composer deptrac`
- To clear cache: `./vendor/bin/sail artisan optimize:clear`
- Optimize autoloader: `./vendor/bin/sail composer install --optimize-autoloader`
- All the usual artisan commands are available through `./vendor/bin/sail artisan`

# Functional knowledge
- a Confirmed user is a user with role `user-confirmed`
- a non-confirmed user is a user with role `user`

# Architecture details
- We use a Domain Oriented Architecture, with modules located in app/Domains
- No test, no Middleware, no blade component should be created outside of app/Domains/<domain> subfolders unless explicitly requested
- Controllers are not allowed to call the Database directly (or through models). They must use a service. 
- At the root of the domain (/app/Domains/<domain name>), only Public, Private, Database and Tests folders are allowed
- Refer to docs/Domain_Structure.md whenever creating a file
- Database Migrations goes into /app/Domains/<relevant domain>/Database/Migrations folder
- Database Migrations should never define foreign keys to tables not located inside the Domain. In particular, there should be no foreign key towards 'users' table from outside of Auth domain

## Domain Registry

| Domain | Path | Responsibilities | Tables |
|--------|------|-----------------|--------|
| **Admin** | `app/Domains/Admin` | Filament-based admin panel UI; entry point for all admin operations | _(none — uses other domains' tables)_ |
| **Administration** | `app/Domains/Administration` | Admin layout, logs viewer, maintenance mode controller | _(none)_ |
| **Auth** | `app/Domains/Auth` | User authentication (Breeze), registration, roles, activation codes, promotion requests | `users`, `roles`, `role_user`, `user_activation_codes`, `user_promotion_request`, `password_reset_tokens`, `sessions` |
| **Calendar** | `app/Domains/Calendar` | Time-bound activities (contests, challenges) with a plugin-based activity-type registry | `calendar_activities`, `calendar_jardino_*`, `calendar_secret_gift_*` |
| **Comment** | `app/Domains/Comment` | Pluggable comment system with per-entity policy registry; consumed by Story, News, etc. | `comments` |
| **Config** | `app/Domains/Config` | Site configuration and feature toggles, readable by other domains via `ConfigPublicApi` | `config_feature_toggles`, `config_parameter_values` |
| **Dashboard** | `app/Domains/Dashboard` | Authenticated user dashboard page | _(none)_ |
| **Discord** | `app/Domains/Discord` | Discord bot integration; user connection via code exchange | `discord_connection_codes`, `discord_users` |
| **Events** | `app/Domains/Events` | Domain event bus and audit log infrastructure; cross-domain communication backbone | `domain_events` |
| **FAQ** | `app/Domains/FAQ` | FAQ categories and questions with Filament admin panel | `faq_categories`, `faq_questions` |
| **Home** | `app/Domains/Home` | Home page, aggregates data from multiple domains | _(none)_ |
| **Message** | `app/Domains/Message` | Private messages between users (incomplete) | `messages`, `message_deliveries` |
| **Moderation** | `app/Domains/Moderation` | User reporting with pluggable topic registry; moderators review reports in Admin | `moderation_reasons`, `moderation_reports` |
| **News** | `app/Domains/News` | News articles with publish/unpublish workflow and homepage carousel | `news` |
| **Notification** | `app/Domains/Notification` | Cross-domain user notification system with extensible content types | `notifications`, `notification_reads` |
| **Profile** | `app/Domains/Profile` | User profile, picture, bio, social links | `profile_profiles` |
| **ReadList** | `app/Domains/ReadList` | Reading bookmarks ("pile à lire") with progress tracking and infinite scroll | `read_list_entries` |
| **Search** | `app/Domains/Search` | Global search across stories and profiles; renders inline results below top bar | _(none)_ |
| **Settings** | `app/Domains/Settings` | Extensible user preferences system; other domains register their own tabs/parameters | `settings` |
| **Shared** | `app/Domains/Shared` | Shared components, CSS/JS, translations, common utilities used across all domains | _(none)_ |
| **StaticPage** | `app/Domains/StaticPage` | Admin-managed static pages with publish/draft lifecycle; served via catch-all routes with slug-map caching | `static_pages` |
| **Story** | `app/Domains/Story` | Core domain: story and chapter CRUD, publication, credits, reading progress | `stories`, `story_chapters`, `story_chapter_credits`, `story_collaborators`, `story_genres`, `story_reading_progress`, `story_trigger_warnings` |
| **StoryRef** | `app/Domains/StoryRef` | Reference data for stories (genres, types, statuses, audiences, trigger warnings, etc.) | `story_ref_audiences`, `story_ref_copyrights`, `story_ref_feedbacks`, `story_ref_genres`, `story_ref_statuses`, `story_ref_trigger_warnings`, `story_ref_types` |


# Laravel Coding Standards

## Models
- Use singular names (User, Novel, Chapter)
- Define relationships explicitly
- Use `protected $fillable`
- Implement soft deletes where appropriate: `use SoftDeletes`
- Add validation rules in model methods
- Use Eloquent conventions for foreign keys

## Controllers
- Use form requests for validation
- Follow RESTful naming conventions

## Migrations
- Use descriptive migration names
- Prefix : YYYY_MM_DD_HHiiss_<migration_name>
- Always add `down()` methods
- Use foreign key constraints only for tables from same domain.

- Add indexes for search columns
- Use proper column types (text for long content, string for short)

## Routes
- Group related routes: `Route::prefix('admin')->group()`
- Use route model binding: `Route::get('/novel/{novel}', [Controller::class, 'show'])`
- Name all routes: `->name('novels.show')`
- Apply middleware appropriately

# Frontend Guidelines

## Alpine.js Best Practices
- Keep `x-data` objects simple and focused
- Use `x-show` for toggles, `x-if` for conditional rendering
- Prefix Alpine directives: `x-data`, `x-model`, `x-on:click`
- Extract complex logic to separate functions
- Use `x-init` for component initialization
- Avoid deep nesting in Alpine components

## Blade Templates
- Use `@extends` and `@section` for layouts
- Prefer `@include` for reusable components
- Use `{{ }}` for escaped output, `{!! !!}` only when necessary
- Keep logic minimal in templates

## CSS/Styling
- Use Tailwind classes directly in templates
- Keep utility-first approach
- Use responsive prefixes: `md:`, `lg:`

# Database Design

## Relationships
- Use proper relationship types: `hasMany`, `belongsTo`, `belongsToMany`
- Add foreign key constraints in migrations
- Use eager loading to prevent N+1 queries

# Code Quality Rules

## General Practices
- Follow PSR-12 coding standards

## Testing Considerations
- Write integration tests for most flows

## Security
- Always validate and sanitize user input
- Use CSRF protection on forms
- Implement proper authorization checks
- Never trust frontend data