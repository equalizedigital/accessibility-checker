#!/bin/bash

mkdir -p ./dist
mkdir -p ./tests/e2e/.auth

# note, when pulling from github you must include the commit hash
# https://stackoverflow.com/questions/22608398/composer-update-not-pulling-latest-dev-master
rm composer.lock
composer install

# npm run build
npm run build

