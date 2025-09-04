# Esperluettes

This is a repository to provide writing communities with a website to share and comment their writings.

The documentation will improve as the project progresses. For now, you can have a look at :
- How to setup [Setup](docs/Setup.md) the environment locally to work with it
- The current chosen [Architecture](docs/Architecture.md).

## Comment Domain (Rendering and Endpoints)

The Comment domain uses server-rendered Blade fragments for lazy loading instead of JSON endpoints. This makes most of the UI testable via standard Feature tests (asserting on HTML) without a browser runner.

- Fragment endpoint: `GET /comments/fragments?entity_type={type}&entity_id={id}&page={n}&per_page={m}` returns an HTML snippet of list items.
- Create comment: `POST /comments` (CSRF-protected form)
- Update comment: `PATCH /comments/{id}` (author only, CSRF-protected form)

For the complete specification and testing strategy, see [docs/Feature_Planning/Comment.md](docs/Feature_Planning/Comment.md).

## Contributing

Please read our [Contributing Guide](CONTRIBUTING.md) for the workflow, Conventional Commits policy, and local quality checks (Deptrac + commitlint). All work happens in a single GitHub repository via Pull Requests.

## Deploying

To prepare the files, just launch

> npm run build-and-deploy:full

You should then get a dist/ folder than you can send to your FTP server.

If you only made changes to the app folder (no new dependency), you can run:

> npm run build-and-deploy:app-only

Then you should take the content of the **sync** folder
