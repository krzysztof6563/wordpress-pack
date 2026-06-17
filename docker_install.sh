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
PLUGINS=(tinymce-advanced contact-form-7 custom-post-type-ui simple-page-ordering wordpress-seo webp-express svg-support secure-custom-fields)
for plugin in ${PLUGINS[*]}; do 
    docker compose exec web bash -c "wp --allow-root  plugin install $plugin --activate"; 
done

echo "Installing Timber Starter Theme and adding webpack loader"
docker compose exec web bash -c "cd wp-content/themes && composer create-project upstatement/timber-starter-theme --no-dev"
docker compose exec web bash -c "cp -r wordpress-pack/timber-starter-theme/{*,.*} wp-content/themes/timber-starter-theme"
docker compose exec web bash -c "sed -i \"\|require_once __DIR__ . '/src/StarterSite.php';|i require_once __DIR__ . '/src/twig-extension-webpack-loader.php';\" wp-content/themes/timber-starter-theme/functions.php"
docker compose exec web bash -c "echo '{{ webpack_styles('app') }}' >> wp-content/themes/timber-starter-theme/views/html-header.twig"
docker compose exec web bash -c "sed -i 's|</body>|{{ webpack_scripts('app') }}\n</body>|' wp-content/themes/timber-starter-theme/views/base.twig"
docker compose exec web bash -c "wp --allow-root theme activate timber-starter-theme"
docker compose exec web bash -c "rm -r wp-content/themes/twentytwenty*"
sudo chmod -R 777 ../

echo "Setting up homepage and disabling comments"
docker compose exec web bash -c "wp --allow-root option set show_on_front page"
docker compose exec web bash -c "wp --allow-root option set page_on_front 2"
docker compose exec web bash -c "wp --allow-root option set default_comment_status \"\""
docker compose exec web bash -c "wp --allow-root option set comment_moderation \"\""
docker compose exec web bash -c "wp --allow-root option set comment_registration \"\""
docker compose exec web bash -c "wp --allow-root option set close_comments_for_old_posts \"\""
docker compose exec web bash -c "wp --allow-root option set page_comments \"\""

echo "Deleting default posts and comments"
# docker compose exec web bash -c "wp --allow-root post delete \$(wp --allow-root post list --post_type=comment --format=ids)"
docker compose exec web bash -c "wp --allow-root post delete \$(wp --allow-root post list --post_type=post --format=ids)"


echo -e "\n\n"

echo -e "🙌 Wordpress should be available at http://localhost:8080"
echo "👤 User: admin"
echo -e "🔑 Password: admin\n"

echo "💿 Remember to install node dependencies via \`npm i\` in \`wp-content/themes/timber-starter-theme\` and start webpack with \`npm run dev\`"
