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

**IMPORTANT**: You must shut down vite dev server (`npm run dev`) before running the deployment script. Else the assets are going to point to your local server

To prepare the files, just launch

> npm run package

And choose a version number of let the app auto-generate one.

You should get two zip files in the `dist` folder, one for test, one for production.

Note: on first deployment, you should create a symlink between `public_html/storage` and `storage/app/public` folder.