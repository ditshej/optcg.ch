# Deploy

The server pulls the repository itself; there is no build artefact to upload.
`deploy.sh` runs locally and opens an SSH session that runs `_deploy.sh` on
the server. `_deploy.sh` checks the branch and a clean working copy, does
`git pull --ff-only`, then installs the Composer dependencies
(`symfony/yaml`, `justinrainbow/json-schema`) the page needs at runtime.

The web server's document root must point to `public/`.

## Running a deploy

1. Copy `.env.deploy.example` to `.env.deploy` (untracked, stays local) and
   fill in the real values: `DEPLOY_USER`, `DEPLOY_HOST`, `DEPLOY_PORT`,
   `DEPLOY_PATH` (the server-side checkout directory), `DEPLOY_BRANCH`, and,
   if the server's `php`/`composer` are not on the default `PATH`,
   `DEPLOY_PHP_BIN` / `DEPLOY_COMPOSER_BIN`.
2. Run `./deploy.sh`.
3. Rolling back is a `git revert` on the branch and another deploy.

**Nobody runs a deploy against the live server without an explicit go from
the repository owner.**
