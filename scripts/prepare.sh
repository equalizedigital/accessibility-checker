# npm run start --update # wp-env removed
# npm run stop # wp-env removed

mkdir -p ./dist
mkdir -p ./tests/e2e/.auth
mkdir -p ./patches
# mkdir -p ./.wp-env # wp-env removed

# note, when pulling from github you must include the commit hash
# https://stackoverflow.com/questions/22608398/composer-update-not-pulling-latest-dev-master
rm composer.lock
composer install

# cp -r -n ./vendor/equalizedigital/accessibility-checker-wp-env/.wp-env/ ./.wp-env # wp-env removed
# cp -r -n ./vendor/equalizedigital/accessibility-checker-wp-env/.wp-env.json ./ # wp-env removed
# cp -r ./vendor/equalizedigital/accessibility-checker-wp-env/patches/* ./patches/ # wp-env removed

# npm run start # wp-env removed
# ./.wp-env/scripts/init.sh # wp-env removed
npm run build

