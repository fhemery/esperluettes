# Environment setup

This project uses Docker and Laravel `Sail` command to work without installing too many things on your machine.

## Pre-requisite
You need to have Docker installed on your machine, as well as a recent version of nodejs

## First setup - Via Docker containers

We are using Docker and sail to simplify setup. You need to have Docker installed (or possibly Docker Desktop and WSL if you are using Windows).

0. Specific steps for Windows: install WSL then Docker Desktop. If it works you should be able to launch docker command in WSL shell. Don't forget to launch Docker Desktop before doing any other steps.

1. We first need to get the vendor module. For this :

   For Linux (BASH) : docker run --rm -it -v "$PWD:/app" -w /app -e COMPOSER_MEMORY_LIMIT=-1 laravelsail/php84-composer composer install --no-interaction --prefer-dist --ignore-platform-reqs 
   For Windows: you should launch the same command inside WSL, because if you do so inside Powershell you will have conflicted rights between linux & Windows. Note: your repo in WSL is inside /mnt/c/path_to_your_repo if your repo on Windows is in path_to_your_repo.

Use `docker compose` to first start the containers :

> docker compose up -d

2. Now sail should be available, you should be able to launch it (if in windows, you launch it inside WSL and not powershell)

> ./vendor/bin/sail up -d

3. Create an alias for the sail command (optional) :

>    alias sail="./vendor/bin/sail"

5. Copy the `.env.example` file to `.env`

6. Generate a key and put it in the .env file, section "APP_KEY"

> sail artisan key:generate

7. Fill the database and seed it

> sail artisan migrate:install 

> sail artisan migrate

> sail artisan db:seed

8. Run npm build to create the assets

> npm install

> npm run build

You are up and running on : http://localhost. The first user you can log with is admin@example.com / password.

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