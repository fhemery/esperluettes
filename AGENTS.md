# Environment Commands

## Docker/Sail Commands
- Use `sail` instead of direct PHP/Composer commands
- To run all tests : `./vendor/bin/sail artisan test:parallel [--filter=<your filter>] [list of domains to tests]`
- Composer : `./vendor/bin/sail composer` 
- Deptrac : `./vendor/bin/sail composer deptrac`
- Artisan commands: `./vendor/bin/sail artisan`

# Functional knowledge
- a Confirmed user is a user with role `user-confirmed`
- a non-confirmed user is a user with role `user`

# Architecture details
- Domain Oriented Architecture, modules in app/Domains
- No code should be written outside of app/Domains/<domain> subfolders unless explicitly requested
- Controllers call models and database through services.
- Refer to docs/Domain_Structure.md whenever creating a file

## Domain Registry

| Domain | Path | Responsibilities | Tables |
|--------|------|-----------------|--------|
| **Admin** | `app/Domains/Admin` | Filament-based admin panel UI; entry point for all admin operations |
| **Administration** | `app/Domains/Administration` | Admin layout, logs viewer, maintenance mode controller |
| **Auth** | `app/Domains/Auth` | User authentication (Breeze), registration, roles, activation codes, promotion requests |
| **Calendar** | `app/Domains/Calendar` | Time-bound activities (contests, challenges) with a plugin-based activity-type registry |
| **Comment** | `app/Domains/Comment` | Pluggable comment system with per-entity policy registry; consumed by Story, News, etc. |
| **Config** | `app/Domains/Config` | Site configuration and feature toggles, readable by other domains via `ConfigPublicApi` |
| **Dashboard** | `app/Domains/Dashboard` | Authenticated user dashboard page |
| **Discord** | `app/Domains/Discord` | Discord bot integration; user connection via code exchange |
| **Events** | `app/Domains/Events` | Domain event bus and audit log infrastructure; cross-domain communication backbone |
| **FAQ** | `app/Domains/FAQ` | FAQ categories and questions with Filament admin panel |
| **Home** | `app/Domains/Home` | Home page, aggregates data from multiple domains |
| **Message** | `app/Domains/Message` | Private messages between users (incomplete) |
| **Moderation** | `app/Domains/Moderation` | User reporting with pluggable topic registry; moderators review reports in Admin |
| **News** | `app/Domains/News` | News articles with publish/unpublish workflow and homepage carousel |
| **Notification** | `app/Domains/Notification` | Cross-domain user notification system with extensible content types |
| **Profile** | `app/Domains/Profile` | User profile, picture, bio, social links |
| **ReadList** | `app/Domains/ReadList` | Reading bookmarks ("pile à lire") with progress tracking and infinite scroll |
| **Search** | `app/Domains/Search` | Global search across stories and profiles; renders inline results below top bar |
| **Settings** | `app/Domains/Settings` | Extensible user preferences system; other domains register their own tabs/parameters |
| **Shared** | `app/Domains/Shared` | Shared components, CSS/JS, translations, common utilities used across all domains |
| **StaticPage** | `app/Domains/StaticPage` | Admin-managed static pages with publish/draft lifecycle; served via catch-all routes with slug-map caching | 
| **Story** | `app/Domains/Story` | Core domain: story and chapter CRUD, publication, credits, reading progress |
| **StoryRef** | `app/Domains/StoryRef` | Reference data for stories (genres, types, statuses, audiences, trigger warnings, etc.) |


# Laravel Coding Standards

## Models
- Use `protected $fillable`
- Add validation rules in model methods
- Use Eloquent conventions for foreign keys

## Controllers
- Use form requests for validation
- Follow RESTful naming conventions

## Migrations
- Format: YYYY_MM_DD_HHiiss_<descriptive_migration_name>
- Always add `down()` methods
- Use foreign key constraints only for tables from same domain.
- Add indexes for search columns

# Frontend Guidelines
- Use Blade and AlpineJS best practices
- Use tailwind utility-first approach with responsive prefixes

# Database Design
- Use eager loading to prevent N+1 queries

# Code Quality Rules

## General Practices
- Follow PSR-12 coding standards

## Testing Considerations
- Write integration tests for most flows
- Write failing tests before implementing

## Security
- Always validate and sanitize user input
- Use CSRF protection on forms
- Implement proper authorization checks
- Never trust frontend data