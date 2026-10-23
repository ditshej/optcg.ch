#!/bin/sh
set -eu

# Runs on the Mac. Builds the production vendor/ from the branch on GitHub,
# uploads it, then triggers _deploy.sh over SSH, which pulls the code.
# Composer runs here because the server's SSH shell has no PHP 8.5.

cd "$(dirname "$0")"

if [ ! -f .env.deploy ]; then
    echo "Error: .env.deploy not found. Copy .env.deploy.example and fill in the server values." >&2
    exit 1
fi

set -a
# shellcheck source=/dev/null
# (.env.deploy is untracked and machine-local; only .env.deploy.example ships in the repo)
. ./.env.deploy
set +a

BRANCH="${DEPLOY_BRANCH:-main}"

# Build from the pushed branch, not from the local working tree, so vendor/
# matches the composer.lock the server is about to pull. The local vendor/
# (with dev packages) stays untouched.
build_dir=$(mktemp -d)
trap 'rm -rf "$build_dir"' EXIT

echo "Building vendor/ from origin/$BRANCH..."
git fetch --quiet origin "$BRANCH"
git archive FETCH_HEAD composer.json composer.lock src | tar -x -C "$build_dir"
composer install --working-dir="$build_dir" --no-dev --optimize-autoloader --no-interaction --quiet

echo "Uploading vendor/..."
rsync -az --delete -e "ssh -p $DEPLOY_PORT" \
    "$build_dir/vendor/" \
    "$DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PATH/vendor/"

echo "Deploying..."
ssh -p "$DEPLOY_PORT" "$DEPLOY_USER@$DEPLOY_HOST" -t \
    "cd '$DEPLOY_PATH' && DEPLOY_BRANCH='$BRANCH' sh ./_deploy.sh"
