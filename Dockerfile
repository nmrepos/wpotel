FROM php:8.3.6-cli-alpine3.19

RUN apk update \
    && apk add curl unzip \
# install the PHP extensions we need
    && apk add wget mysql mysql-client php82-mysqli
RUN docker-php-ext-install mysqli

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install OpenTelemetry PHP extension
RUN apk add --no-cache autoconf g++ make \
    && pecl install opentelemetry-1.1.0 \
    && docker-php-ext-enable opentelemetry \
    && apk del autoconf g++ make

RUN curl https://wordpress.org/latest.zip -o /tmp/wordpress.zip
RUN unzip -d /var/local /tmp/wordpress.zip && chown -R www-data:www-data /var/local/wordpress

USER www-data:www-data
WORKDIR /var/local/wordpress

RUN mv wp-content wp-content.bak
RUN mkdir /var/local/wordpress/wp-content
RUN chown www-data:www-data /var/local/wordpress/wp-content
VOLUME /var/local/wordpress/wp-content

# Copy composer files and install dependencies
COPY ./composer.json composer.json
RUN composer install --no-dev --optimize-autoloader

# Copy configuration files
COPY ./wp-config.php wp-config.php
COPY ./phpinfo.php phpinfo.php
COPY ./start_wordpress.sh start_wordpress.sh

# Copy the microfrontend plugin
COPY ./microfrontend-embed.php /tmp/microfrontend-embed.php

# Configure PHP for OpenTelemetry
USER root
COPY ./otel.php.ini /usr/local/etc/php/conf.d/otel.ini

USER www-data:www-data

CMD sh start_wordpress.sh