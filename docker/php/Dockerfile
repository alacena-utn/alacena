# PHP 8.4 + Apache, con PDO MySQL, mod_rewrite, Composer y envío de correo a Mailpit
FROM php:8.4-apache

# git y unzip: los necesita Composer para descargar paquetes
# msmtp: hace que la función mail() de PHP entregue los correos a Mailpit (bandeja de prueba)
RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip msmtp \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-install pdo_mysql \
 && a2enmod rewrite \
 && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && printf '<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>\n' > /etc/apache2/conf-available/alacena.conf \
 && a2enconf alacena \
 && printf 'sendmail_path = "/usr/bin/msmtp -t --host=mailpit --port=1025 --from=no-responder@alacena.test"\n' > /usr/local/etc/php/conf.d/correo.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
