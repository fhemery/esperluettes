# Environment setup

This project uses Docker and Laravel `Sail` command to work without installing anything on your machine.

## Pre-requisite
You need to have Docker installed on your machine, as well as a recent version of nodejs

## First setup

1. Use `docker compose` to first start the containers :

> docker compose up -d

2. Use `docker ps` to locate the sail container name (for example : `esperluettes-laravel.test`)

> docker ps | grep sail

3. Launch composer to get the vendor folder :

> docker compose exec [container_name] composer install

4. Create an alias for the `sail` command (optional) :

> alias sail="./vendor/bin/sail"

5. Copy the `.env.example` file to `.env`

6. Generate a key and put it in the .env file, section "APP_KEY"

> sail artisan key:generate

7. Run npm build to create the assets

> npm run build

You are up and running!

## Working with the app

### Start the environment 

> sail up -d

### Stop the environment 

> sail down

### Regenerate javascript and tailwind classes (whenever you touch javascript)

> npm run build

## Other Essential Laravel Commands
- Artisan commands: `sail artisan [command]`
- Composer commands: `sail composer [command]`
- Database access: `sail mysql`

### Laravel Commands
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