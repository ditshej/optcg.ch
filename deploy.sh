#!/bin/sh
set -eu

# Local trigger for a deploy. The server pulls the repository itself; this
# script only opens an SSH session and runs _deploy.sh there.

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

echo "Deploying..."
ssh -p "$DEPLOY_PORT" "$DEPLOY_USER@$DEPLOY_HOST" -t \
    "cd '$DEPLOY_PATH' && DEPLOY_BRANCH='${DEPLOY_BRANCH:-main}' DEPLOY_PHP_BIN='${DEPLOY_PHP_BIN:-php}' DEPLOY_COMPOSER_BIN='${DEPLOY_COMPOSER_BIN:-composer}' bash ./_deploy.sh"
