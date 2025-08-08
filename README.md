# Esperluettes

This is a repository to provide writing communities with a website to share and comment their writings.

The documentation will improve as the project progresses. For now, you can have a look at :
- How to setup [[docs/Setup.md|Setup]] the environment locally to work with it
- The current chosen [[docs/Architecture.md|Architecture]].

## Deploying

To prepare the files, just launch

> npm run build-and-deploy:full

You should then get a dist/ folder than you can send to your FTP server.

If you only made changes to the app folder (no new dependency), you can run:

> npm run build-and-deploy:app-only

Then you should take the content of the **sync** folder