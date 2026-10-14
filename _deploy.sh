#!/bin/sh
set -eu

# Runs on the server, inside the checkout. Checks the branch and a clean
# worktree, pulls fast-forward only, then installs the Composer dependencies
# (symfony/yaml, justinrainbow/json-schema) needed to read and validate the
# YAML data at runtime. No migrations, no cache, no other build step.
#
# Rolling back is a `git revert` on the branch and another deploy.

# Overridable via .env.deploy (passed through by deploy.sh over SSH).
BRANCH="${DEPLOY_BRANCH:-main}"
PHP_BIN="${DEPLOY_PHP_BIN:-php}"
COMPOSER_BIN="${DEPLOY_COMPOSER_BIN:-composer}"

current_branch=$(git rev-parse --abbrev-ref HEAD)
if [ "$current_branch" != "$BRANCH" ]; then
    echo "Deploy aborted: server is on branch '$current_branch', expected '$BRANCH'." >&2
    echo "Fix on the server with: git checkout $BRANCH" >&2
    exit 1
fi

# diff-index compares stat data; refresh first so a touched but unchanged
# file (the host fixing permissions) does not count as a change.
git update-index -q --refresh || true
if ! git -c core.fileMode=false diff-index --quiet HEAD --; then
    echo "Deploy aborted: uncommitted changes in the server working tree." >&2
    echo "Inspect on the server with: git status" >&2
    exit 1
fi

git pull --ff-only origin "$BRANCH"

"$PHP_BIN" -d memory_limit=2048M "$COMPOSER_BIN" install --no-dev -o
