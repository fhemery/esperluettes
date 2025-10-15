---
trigger: always_on
---

# Environment Commands

## Docker/Sail Commands
- Use `sail` instead of direct PHP/Composer commands
- To run tests : `./vendor/bin/sail artisan test`
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
- Controllers are not allowed to call the Databse directly (or through models). They must use a service. 
- At the root of the domain (/app/Domains/<domain name>), only Public, Private, Database and Tests folders are allowed
- Refer to docs/Domain_Structure.md whenever creating a file
- Database Migrations goes into /app/Domains/<relevant domain>/Database/Migrations folder
- Database Migrations should never define foreign keys to tables not located inside the Domain. In particular, there should be no foreign key towards 'users' table from outside of Auth domain


# Laravel Coding Standards

## Models
- Use singular names (User, Novel, Chapter)
- Define relationships explicitly
- Use `protected $fillable`
- Implement soft deletes where appropriate: `use SoftDeletes`
- Add validation rules in model methods
- Use Eloquent conventions for foreign keys

## Controllers
- Keep controllers thin, logic in models/services
- Use resource controllers: `Route::resource('novels', NovelController::class)`
- Return views with compact data: `return view('novels.show', compact('novel'))`
- Use form requests for validation
- Follow RESTful naming conventions

## Migrations
- Use descriptive migration names
- Prefix all migrations with date and time of migration YYYYMMDD_HHiiss_<migration_name>
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
- Prefix Alpine directives: `x-data`, `x-model`, `@click`
- Extract complex logic to separate functions
- Use `x-init` for component initialization
- Avoid deep nesting in Alpine components

## Blade Templates
- Use `@extends` and `@section` for layouts
- Prefer `@include` for reusable components
- Use `{{ }}` for escaped output, `{!! !!}` only when necessary
- Keep logic minimal in templates
- Use `@auth`, `@guest` for authentication checks

## CSS/Styling
- Use Tailwind classes directly in templates
- Keep utility-first approach
- Create component classes for repeated patterns
- Use responsive prefixes: `md:`, `lg:`
- Maintain consistent spacing scale

# Database Design

## Naming Conventions
- Tables: plural snake_case (users, novel_chapters)
- Columns: snake_case (created_at, user_id)
- Foreign keys: singular_table_id (user_id, novel_id)
- Pivot tables: alphabetical order (chapter_reviews)

## Relationships
- Use proper relationship types: `hasMany`, `belongsTo`, `belongsToMany`
- Add foreign key constraints in migrations
- Consider using polymorphic relationships for flexible designs

# Novel Platform Specific Rules

## Authentication & Authorization
- Use Laravel's built-in authentication (breeze)
- Implement policies for model authorization
- Gate checks in controllers and views
- Use middleware for route protection

## Content Management
- Store rich text in TEXT columns
- Validate chapter word counts
- Use eager loading to prevent N+1 queries

## Admin Panel (Filament)
- Create resources for all major models
- Implement proper CRUD operations
- Add search and filtering capabilities
- Use bulk actions for moderation

# Code Quality Rules

## General Practices
- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Write docblocks for complex methods
- Keep methods under 20 lines when possible
- Use type hints for method parameters and returns

## Testing Considerations
- Write feature tests for critical user flows
- Test model relationships and constraints
- Mock external services
- Use factories for test data generation

## Security
- Always validate and sanitize user input
- Use CSRF protection on forms
- Implement proper authorization checks
- Never trust frontend data
- Use prepared statements (Eloquent does this automatically)

## Performance
- Use eager loading: `with(['relation'])`
- Implement database indexes for search columns
- Cache expensive queries when appropriate
- Optimize image uploads (book covers)
- Use pagination for large result sets

## Naming Files
- Controllers: PascalCase + Controller suffix
- Models: PascalCase, singular
- Views: kebab-case, grouped in folders
- Migrations: snake_case with timestamp