# Environment setup

To setup, you need:
- [Nodejs](https://nodejs.org) - Take the LTS version

We favor setup through Docker containers and sail, but if you are used to PHP, manual setup could work. Check [alternative setup](./Setup_alternatives.md) for more details.

## First setup - Via Docker containers
We are using `Docker` and `sail` to simplify setup. You need to have Docker installed (or possibly Docker Desktop and WSL if you are using Windows).

1. We first need to get the vendor module. For this :
> For Linux (BASH) : docker run --rm -it -v "$PWD:/app" -w /app -e COMPOSER_MEMORY_LIMIT=-1 laravelsail/php84-composer composer install --no-interaction --prefer-dist --ignore-platform-reqs
> For Windows (POWERSHELL) : docker run --rm -it -v "$(Get-Location):/app" -w /app -e COMPOSER_MEMORY_LIMIT=-1 laravelsail/php84-composer composer install --no-interaction --prefer-dist --ignore-platform-reqs

2. Now sail should be available, you should be able to launch it

> ./vendor/bin/sail up -d

3. Create an alias for the `sail` command (optional) :

> alias sail="./vendor/bin/sail"

4. Copy the `.env.example` file to `.env`

5. Generate a key and put it in the .env file, section "APP_KEY"

> sail artisan key:generate

6. Fill the database and seed it

> sail artisan migrate:install 
> sail artisan db:seed

7. Run npm build to create the assets

> npm run build

You are up and running on : http://localhost

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