#!/bin/bash
set -e

# Start the containers in the background, remove orphans, and don't fail if already running
if ! docker compose up -d --remove-orphans; then
  echo "Failed to start containers. Exiting."
  exit 1
fi

# Wait for MySQL to be ready (simple check, with retries)
echo "Waiting for MySQL to be ready..."
MAX_TRIES=30
TRIES=0
until docker compose exec -T db-phpunit bash -c 'mysqladmin ping -h"localhost" --silent' 2>/dev/null; do
  TRIES=$((TRIES+1))
  if [ "$TRIES" -ge "$MAX_TRIES" ]; then
    echo "MySQL did not become ready in time. Exiting."
    exit 1
  fi
  sleep 2
done

echo "Dropping WordPress test database if it exists..."
docker compose exec phpunit mysql -hdb-phpunit -uwordpress -pwordpress -e "DROP DATABASE IF EXISTS wordpress;"

echo "Installing WordPress test environment..."
if ! docker compose exec phpunit bash -c 'export WP_CORE_DIR=/tmp/wordpress && tests/bin/install-wp-tests.sh wordpress wordpress wordpress db-phpunit'; then
  echo "Failed to install WordPress test environment. Exiting."
  exit 1
fi

echo "Running PHPUnit tests..."
docker compose exec phpunit vendor/bin/phpunit
