# Environment setup

This project uses Docker and Laravel `Sail` command to work without installing anything on your machine.

## Pre-requisite
You need to have Docker installed on your machine

## First setup

1. Use `docker compose` to first start the containers :

> docker compose up -d

2. Use `docker ps` to locate the sail container name (for example : `esperluettes-laravel.test`)

> docker ps | grep sail

3. Launch composer to get the vendor folder :

> docker compose exec [container_name] composer install

4. Create an alias for the `sail` command (optional) :

> alias sail="./vendor/bin/sail"

## Working with the app

Use `sail` instead of direct PHP/Composer commands:
- Start environment: `sail up -d`
- Stop environment: `sail down`
- Artisan commands: `sail artisan [command]`
- Composer commands: `sail composer [command]`
- Database access: `sail mysql`

### Essential Laravel Commands
- Create model: `sail artisan make:model ModelName -m`
- Create controller: `sail artisan make:controller ControllerName`
- Run migrations: `sail artisan migrate`
- Create migration: `sail artisan make:migration create_table_name`
- Create seeder: `sail artisan make:seeder TableSeeder`
- Clear cache: `sail artisan cache:clear`
- Generate key: `sail artisan key:generate`

### Filament Commands (for admin)
- Create resource: `sail artisan make:filament-resource ModelName`
- Create user: `sail artisan make:filament-user`
- Create page: `sail artisan make:filament-page PageName`