# Environment setup

This project uses Docker and Laravel `Sail` command to work without installing too many things on your machine.
To setup, you need:
- [Nodejs](https://nodejs.org) - Take the LTS version

We favor setup through Docker containers and sail, but if you are used to PHP, manual setup could work. Check [alternative setup](./Setup_alternatives.md) for more details.

## First setup - Via Docker containers
We are using Docker and sail to simplify setup. You need to have Docker installed (or possibly Docker Desktop and WSL if you are using Windows).

0. Specific steps for Windows: install WSL then Docker Desktop. If it works you should be able to launch docker command in WSL shell. Don't forget to launch Docker Desktop before doing any other steps.

Ensure you have a distribution enabled, else the command won't work
> wsl --list

If you have no distribution enabled, or only Docker Desktop, run:
```bash
wsl --install
wsl --set-default Ubuntu
```

Then running `wsl --list` again should show ubuntu as default.

1. We first need to get the vendor module. For this :

From the root of the repository, launch: 

> docker run --rm -it -v "$PWD:/app" -w /app -e COMPOSER_MEMORY_LIMIT=-1 laravelsail/php84-composer composer install --no-interaction --prefer-dist --ignore-platform-reqs

Note: For **Windows**: to avoid permission issues, you should launch command from WSL (not from Powershell), from `/mnt/c/path_to_your_repo`.

2. Now sail should be available, you should be able to launch it (if in windows, you launch it inside WSL and not powershell)

> ./vendor/bin/sail up -d

3. Create an alias for the sail command (optional) :

>    alias sail="./vendor/bin/sail"

4. Copy the `.env.example` file to `.env`

5. Generate a key and put it in the .env file, section "APP_KEY"

> sail artisan key:generate

6. Fill the database and seed it

> sail artisan migrate:install 

> sail artisan migrate

> sail artisan db:seed

7. Ensure resources like images are correctly shared

> sail artisan storage:link

8. Run npm build to create the assets

> npm install

> npm run build

You are up and running on : http://localhost. The first user you can log with is admin@example.com / password.

## First steps
The admin user should work just fine, but it's better to create a new user to test the app and to give admin rights to this new user. There is no need for activation code by default.


You are going to need to confirm the email. Email are sent to the [log file](../storage/logs/laravel.log).


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
