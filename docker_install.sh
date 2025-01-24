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
PLUGINS=(tinymce-advanced contact-form-7 custom-post-type-ui simple-page-ordering wordpress-seo webp-express svg-support)
for plugin in ${PLUGINS[*]}; do 
    docker compose exec web bash -c "wp --allow-root  plugin install $plugin --activate"; 
done
docker compose exec web bash -c "wget https://github.com/BrodNet-Internet-Applications/custom-field-suite/archive/refs/tags/2.6.7.zip -O wp-content/plugins/custom-field-suite.zip"; 
docker compose exec web bash -c "unzip wp-content/plugins/custom-field-suite.zip -d wp-content/plugins/tmp/"; 
docker compose exec web bash -c "mkdir -p wp-content/plugins/custom-field-suite";
docker compose exec web bash -c "mv wp-content/plugins/tmp/custom-field-suite-*/* wp-content/plugins/custom-field-suite/";
docker compose exec web bash -c "wp --allow-root plugin activate custom-field-suite"; 
docker compose exec web bash -c "rm -r wp-content/plugins/tmp"; 

echo "Installing modified Timber Starter Theme"
docker compose exec web bash -c "cp -r wordpress-pack/bykon-2024 wp-content/themes/"
docker compose exec web bash -c "cd wp-content/themes/bykon-2024 && echo '{}' > composer.json && composer config --no-plugins allow-plugins.composer/installers true && composer require timber/timber:^1.0 -n"
docker compose exec web bash -c "wp --allow-root theme activate bykon-2024"
docker compose exec web bash -c "rm -r wp-content/themes/twentytwenty*"
sudo chmod -R 777 .chmod -r

echo "Setting up homepage"
docker compose exec web bash -c "wp --allow-root option set show_on_front page"
docker compose exec web bash -c "wp --allow-root option set page_on_front 2"

echo "Deleting default posts and comments"
docker compose exec web bash wp --allow-root post delete $(docker compose exec web bash wp --allow-root post list --post_type=post --format=ids)
docker compose exec web bash wp --allow-root post delete $(docker compose exec web bash wp --allow-root post list --post_type=comment --format=ids)

echo -e "\n\n"

echo -e "🙌 Wordpress should be available at http://localhost:8080"
echo "👤 User: admin"
echo -e "🔑 Password: admin\n"

echo "💿 Remember to install node dependencies via \`yarn install\` in \`wp-content/themes/bykon-2024\` and start webpack with \`yarn dev\`"
