# First steps after installation

The admin user should work just fine, but **it's better to create a new user** (via the registration form) to test the app and to give admin rights to this new user. There is no need for activation code by default.


You are going to need to confirm the email. Email are sent to the log file,
`storage/logs/laravel.log` — not a link, because the file does not exist until
the app has run once, and the `docs` gate checks that every link resolves.