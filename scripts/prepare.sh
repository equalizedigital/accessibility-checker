# npm run start --update # wp-env removed
# npm run stop # wp-env removed

mkdir -p ./dist
mkdir -p ./tests/e2e/.auth
mkdir -p ./patches

# note, when pulling from github you must include the commit hash
# https://stackoverflow.com/questions/22608398/composer-update-not-pulling-latest-dev-master
rm composer.lock
composer install

# npm run build
npm run build

