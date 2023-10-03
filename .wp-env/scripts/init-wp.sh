source ./.wp-env/cfg/.env
npx wp-env run cli wp option update edacp_license_key $LICENSE

npx wp-env run cli wp plugin install accessibility-checker --activate
npx wp-env run cli wp plugin install accessibility-checker-pro --activate
npx wp-env run cli wp plugin install query-monitor --activate

npx wp-env run cli wp post create --post_type='post' --post_title='Bad post' /mnt/a-bad-page.html
npx wp-env run cli wp post create --post_type='page' --post_title='Bad page' /mnt/a-bad-page.html
