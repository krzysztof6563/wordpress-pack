FROM php:7.4-apache
RUN a2enmod rewrite

WORKDIR /var/www

RUN apt-get update \
  && apt-get install -y libzip-dev git wget less libpng-dev dos2unix --no-install-recommends \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

RUN docker-php-ext-install mysqli zip gd

COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf

COPY docker/entrypoint.sh /entrypoint.sh

RUN unix2dos /entrypoint.sh
RUN chmod +x /entrypoint.sh


CMD ["apache2-foreground"]

ENTRYPOINT ["/entrypoint.sh"]

