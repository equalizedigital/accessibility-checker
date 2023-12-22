npm run start --update
npm run stop

mkdir -p ./dist
mkdir -p ./tests/e2e/.auth
mkdir -p ./patches
mkdir -p ./.wp-env

# note, when pulling from github you must include the commit hash
# https://stackoverflow.com/questions/22608398/composer-update-not-pulling-latest-dev-master
rm composer.lock
composer install

cp -r -n ./vendor/equalizedigital/accessibility-checker-wp-env/.wp-env/ ./.wp-env 
cp -r -n ./vendor/equalizedigital/accessibility-checker-wp-env/.wp-env.json ./
cp -r ./vendor/equalizedigital/accessibility-checker-wp-env/patches/* ./patches/

npm run start
./.wp-env/scripts/init.sh
npm run build

