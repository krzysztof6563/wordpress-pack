#!/bin/bash
cd ..
echo "[INFO] Downloading wordpress"
wp core download --locale="pl_PL"

echo -n "Enter database user: "
read DB_USER
echo -n "Enter database name: "
read DB_NAME
echo -n "Enter database host: "
read DB_HOST
echo -n "Enter database password: "
read DB_PASS
echo "[INFO] Creating databse schema"
mysql -u "$DB_USER" -p "$DB_PASS" -h "$DB_HOST" -e "CREATE SCHEMA $DB_NAME"

echo "[INFO] Creating wp-config.php file"
wp config create --dbname="$DB_NAME" --dbuser="$DB_USER"

echo -n "Site's URL: "
read SITE_URL
echo -n "Site's title: "
read SITE_TITLE
echo -n "Admin username: "
read ADMIN_USERNAME
echo -n "Admin e-mail: "
read ADMIN_EMAIL
wp core install --url="$SITE_URL" --title="$SITE_TITLE" --admin_user="$ADMIN_USERNAME" --admin_email="$ADMIN_EMAIL"

PLUGINS=(tinymce-advanced tinymce-advanced contact-form-7 custom-field-suite custom-post-type-ui timber-library simple-page-ordering wordpress-seo)

echo "[INFO] Downloading and installing plugins"
for plugin in ${PLUGINS[*]}
do
    wp plugin install $plugin --activate
done

echo "[INFO] Copying Timber theme and enabling it"
cp -r wp-content/plugins/timber-library/timber-starter-theme/ wp-content/themes/
wp theme activate timber-starter-theme
echo "[INFO] Deleting unneccessary themes"
wp theme delete --all 

echo "[INFO] Adding webpack encore"
cd wp-content/themes/timber-starter-theme
yarn add @symfony/webpack-encore --dev sass sass-loader bootstrap
cp ../../../wordpress-pack/.gitignore .
cp ../../../wordpress-pack/* .
mkdir assets
mkdir assets/css/
touch assets/css/variables.scss
echo "@import \"variables\";" >> assets/css/style.scss
echo "@import \"~bootstrap/scss/bootstrap\";" >> assets/css/style.scss 
echo "import \"./css/style.scss\";" >> assets/app.js
mkdir build
chmod -R 777 build

echo "[INFO] Tidying up"
rm wp_install.sh
rm -rf ../../../wordpress-pack/

echo "[INFO] Opening website $SITE_URL in default browser"
xdg-open $SITE_URL
