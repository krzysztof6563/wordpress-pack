#!/bin/bash

cd ..

BASEDIR="$PWD"
PLUGINS=(tinymce-advanced contact-form-7 custom-field-suite custom-post-type-ui timber-library simple-page-ordering wordpress-seo webp-express svg-support)


function downloadWP() {
    echo "[INFO] Downloading wordpress"
    wp core download --locale="pl_PL"
}

function setupWPDB() {
    read -p "Enter database user [root]: " DB_USER
    DB_USER=${DB_USER:-root}
    read -p "Enter database name: " DB_NAME
    read -p "Enter database host [127.0.0.1]: " DB_HOST
    DB_USER=${DB_USER:-127.0.0.1}
    read -p "Enter database password: " DB_PASS

    echo "[INFO] Creating databse schema"
    mysql -u "$DB_USER" -p "$DB_PASS" -h "$DB_HOST" -e "CREATE SCHEMA $DB_NAME"
    echo "[INFO] Creating wp-config.php file"
    wp config create --dbname="$DB_NAME" --dbuser="$DB_USER"
}

function configureWP() {
    read -p "Site's URL: " SITE_URL
    read -p "Site's title: " SITE_TITLE
    read -p "Admin username [admin]: " ADMIN_USERNAME
    ADMIN_USERNAME=${ADMIN_USERNAME:-admin}
    read -p "Admin e-mail: " ADMIN_EMAIL

    wp core install --url="$SITE_URL" --title="$SITE_TITLE" --admin_user="$ADMIN_USERNAME" --admin_email="$ADMIN_EMAIL"
}

function printPluginsArray() {
    echo "Plugins to be installed:"
    for plugin in ${PLUGINS[*]} 
    do
        echo "· $plugin"
    done
    echo ""
}

function installPlugins() {
    YN="Y"
    while [ "$YN" == "Y" ] || [ "$YN" == "y" ]; do
        clear
        printPluginsArray
        read -p "Add other plugins? [Y/N] " YN
        if [ "$YN" == "Y" ] || [ "$YN" == "y" ]; then
            while [ "$pluginName" != 0 ]; do
                clear
                printPluginsArray
                read -p "Enter plugin slug or 0 to exit: " pluginName
                if [ "$pluginName" != 0 ]; then 
                    PLUGINS+=($pluginName)
                fi
            done
        fi
    done

    echo "[INFO] Downloading and installing plugins"
    for plugin in ${PLUGINS[*]}
    do
        wp plugin install $plugin --activate
    done
}

function removeThemes() {
    echo "[INFO] Deleting unneccessary themes"
    wp theme delete --all 
}

function installTimber() {
    cd $BASEDIR
    echo "[INFO] Copying Timber theme and enabling it"
    cp -r wp-content/plugins/timber-library/timber-starter-theme/ wp-content/themes/
    read -p "Activate Timber Starter theme? [Y/N] " YN
    if [ "$YN" == "Y" ] || [ "$YN" == "y" ]; then
        wp theme activate timber-starter-theme  
    fi

    echo "[INFO] Adding webpack encore"
    cd wp-content/themes/timber-starter-theme
    yarn add @symfony/webpack-encore --dev sass sass-loader bootstrap@5 browser-sync-webpack-plugin browser-sync file-loader webpack-publish-plugin @babel/core
    yarn add core-js 
    echo "[INFO] Copying development files"
    cp ../../../wordpress-pack/.gitignore .
    cp -r ../../../wordpress-pack/* .

    mkdir page-templates
    mkdir templates/page-templates
    rm wp_install.sh
}

function installUnderscoresTheme() {
    echo $BASEDIR
    cd "$BASEDIR/wp-content/themes/"
    git clone https://github.com/automattic/_s
}

function tidyUp() {
    echo "[INFO] Tidying up"
    # rm -rf ../../../wordpress-pack/
}

function openSite() {
    echo "[INFO] Opening website $SITE_URL in default browser"
    xdg-open $SITE_URL
}

function printMenu() {
    clear
    echo "Choose what to do:"
    echo "1) Autopilot [2 3 4 5 6 8 9]"
    echo "2) Download Wordpress Core"
    echo "3) Configure database for Wordpress"
    echo "4) Configure Wordpress settings"
    echo "5) Install plugins"
    echo "6) Install Timber Starter Theme"
    echo "7) Install _s theme"
    echo "8) Remove inactive themes"
    echo "9) Open site"
    echo "0) exit"
}


while [ "$case" != 0 ]; do
    printMenu
    read case;
    case $case in
        1)
            downloadWP
            setupWPDB
            configureWP
            installPlugins
            installTimber
            removeThemes
            tidyUp
            openSite
            ;;
        2)
            downloadWP
            ;;
        3)
            setupWPDB
            ;;
        4)
            configureWP
            ;;
        5)
            installPlugins
            ;;
        6)
            installTimber
            ;;
        7)
            installUnderscoresTheme
            ;;
        8)
            removeThemes
            ;;
        9)
            openSite
            ;;
        0)
            echo "Exiting program"
            ;;
        *)
            echo "Choose an action"
            ;;
    esac
    echo "Press return to continue"
    read pause
done
