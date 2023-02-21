FROM php:8.0-apache
RUN a2enmod rewrite

WORKDIR /var/www

RUN apt-get update \
  && apt-get install -y libzip-dev git wget imagemagick libpng-dev libjpeg-dev libwebp-dev less curl imagemagick webp --no-install-recommends \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# GD with webp only works on PHP > 7.4 
RUN docker-php-ext-configure gd --with-webp 
# You can add more modules if ypu need 
RUN docker-php-ext-install mysqli zip gd 

#RUN pecl install xdebug \
#    && docker-php-ext-enable xdebug

COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod headers

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh


CMD ["apache2-foreground"]

ENTRYPOINT ["/entrypoint.sh"]

