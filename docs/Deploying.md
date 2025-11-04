# Deploying

This project requires PHP >= 8.3 to be deployed. It works perfectly with a shared hosting environment (it is actually developped with this target in mind).

## Prerequisites

- PHP >= 8.3 with the following active modules :
  - mbstring
  - pdo_mysql # or pdo_pgsql or pdo_sqlite...
  - pdo
  - tokenizer
  - xml
  - zip
  - intl
  - dom
  - fileinfo
- MySQL or postgresql >= 8.0
- Web server (Apache or Nginx)

## Generating a package
> npm run package

This command requires Nodejs >= 20, and two files :
- `.env.test` for test environment
- `.env.production` for production environment

It performs the following steps:
- Generates the js assets
- Installs the vendor dependencies
- Creates 2 zip files :
  - `esperluettes-production-<versionNumber>.zip` for production
  - `esperluettes-test-<versionNumber>.zip` for test environment

## Deploying

### [First time] Setting up the database
When your `.env.test` and `.env.production` files are ready, and filled it with your database information, you should setup the migrations table. You can do it remotely, or probably on the server in command line as well (once you have transfered and deployed the zip).

> sail artisan migrate:install --env=<test/production>  # Use PHP directly if you are not running sail

### [First time] Setting up CRON jobs

The system has a few Commands running on CRON JOBs (defined in `bootstrap/app.php`).
You should setup the command to run regularly (every minute would be cool in production). Following command should be set up: 

> cd /path/to/your/project && php artisan schedule:run > /dev/null 2>&1

### Updating the database

> sail artisan migrate --env=<test/production>  # Use PHP directly if you are not running sail

It is highly recommended to clear the cache to be sure you run in the correct environment:

> sail artisan cache:clear && sail artisan migrate --env=<test/production> && sail artisan cache:clear

### Using commands

If you need to execute some specific tasks (registered in the different modules), you can use artisan to perform the operations. For example :

> php artisan story:backfill-chapter-comment-notifications

**CAUTION** : We recommend to launch the command directly from the server.