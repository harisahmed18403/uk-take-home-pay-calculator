#!/usr/bin/env bash

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/no-cap-tools/uk-take-home-pay-calculator}"
LANDING_DIR="${LANDING_DIR:-/var/www/no-cap-tools/root}"
BRANCH="${BRANCH:-main}"

if [[ ! -d "$APP_DIR/.git" ]]; then
    echo "Expected a git checkout at $APP_DIR" >&2
    exit 1
fi

git -C "$APP_DIR" fetch origin "$BRANCH"
git -C "$APP_DIR" checkout "$BRANCH"
git -C "$APP_DIR" pull --ff-only origin "$BRANCH"

cd "$APP_DIR"
composer install --no-dev --optimize-autoloader --no-interaction

install -d "$LANDING_DIR"
install -m 0644 deploy/root/index.html "$LANDING_DIR/index.html"
