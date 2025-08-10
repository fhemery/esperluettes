# Setup alternative

Instead of using Docker and `sail` as described in the [Setup](./Setup.md), you can use a manual setup.
This obviously depends on your platform.

## Windows - XAMPP

1. Install XAMPP
Download from apachefriends.org
This includes PHP, MySQL, and Apache

2. Install Composer

Download from getcomposer.org

3. Clone the repo to XAMPP's htdocs folder 

Or use symlink, or change the default folder, as you are used to

4. Run composer install in your project directory

5. Create an empty database in mysql 

6. Configure your .env file for XAMPP's MySQL

From that point one, you can basically replace all `sail` command by `php` :
- You can use `composer` directly
- But `sail artisan ...` becomes `php artisan ...`