if ! command -v docker &> /dev/null
then
    echo "Docker could not be found"
    exit 1
fi

cd ..
if [ "$1" == "" ] 
then
  echo "[ERROR] Missing site title"
  echo "Usage: ./docker_install.sh SITE_TITLE"
  exit 2
fi

echo "[INFO] Setting up docker";
cp -r wordpress-pack/docker_template/* .
docker compose up -d
cd wordpress-pack
echo "[INFO] Waiting 5s for db to intialize (run script again if it fails)"
sleep 5 
echo "[INFO] Installing and configuring WordPress"
docker compose exec web bash -c "wp --allow-root core download --locale=\"pl_PL\""
docker compose exec web bash -c "wp --allow-root config create --dbname=\"wordpress\" --dbuser=\"root\" --dbpass=\"root\" --dbhost=\"db\" --skip-check --force"
docker compose exec web bash -c "wp --allow-root core install --url=\"http://localhost:8080\" --title=\"$1\" --admin_user=\"admin\" --admin_email=\"mail@example.com\" --admin_password=\"admin\""
echo "[INFO] Installing plugins"
PLUGINS=(tinymce-advanced contact-form-7 custom-field-suite custom-post-type-ui timber-library simple-page-ordering wordpress-seo webp-express svg-support)
for plugin in ${PLUGINS[*]}; do 
    docker compose exec web bash -c "wp --allow-root  plugin install $plugin --activate"; 
done
echo "Installing modified Timber Starter Theme"
docker compose exec web bash -c "cp -r wordpress-pack/timber-starter-theme wp-content/themes/"
docker compose exec web bash -c "wp --allow-root theme activate timber-starter-theme"
docker compose exec web bash -c "rm -r wp-content/themes/twentytwenty*"
sudo chmod -R 777 .
echo "Setting up homepage"
docker compose exec web bash -c "wp --allow-root option set show_on_front page"
docker compose exec web bash -c "wp --allow-root option set page_on_front 2"


echo -e "\n\n"

echo -e "🙌 Wordpress should be available at http://localhost:8080"
echo "👤 User: admin"
echo -e "🔑 Password: admin\n"

echo "💿 Remember to install node dependencies via \`yarn install\` in \`wp-content/themes/timber-starter-theme\` and start webpack with \`yarn dev\`"
