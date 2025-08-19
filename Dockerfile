FROM --platform=linux/amd64 php:8.1.17-apache

## Configure PHP
RUN apt-get update && apt-get upgrade -y && \
    apt-get -y install -qq --force-yes mariadb-client libzip-dev libicu-dev zlib1g-dev libc-client-dev libkrb5-dev gnupg2 libaio1 && \
    docker-php-ext-configure intl && docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install imap exif mysqli pdo pdo_mysql zip intl && \
    echo "short_open_tag=off" >> /usr/local/etc/php/conf.d/syntax.ini && \
    echo "memory_limit=-1" >> /usr/local/etc/php/conf.d/memory_limit.ini && \
    echo "display_errors=0" >> /usr/local/etc/php/conf.d/errors.ini && \
    sed -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!' -ri /etc/apache2/sites-available/000-default.conf && \
    apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
#RUN pecl install -f ssh2-1.1.2 && docker-php-ext-enable ssh2

COPY composer.json ./composer.json
COPY composer.lock ./composer.lock
RUN composer install

## Install NodeJS
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get update && apt-get install -y nodejs=20.17.* build-essential && \
    npm install -g npm yarn && \
    apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

# Copy application files first
COPY --chown=www-data:www-data . .

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/var/sessions && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Switch to www-data user for dependency installation
USER www-data

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Install Node.js dependencies only (build will happen at startup)
RUN yarn install --frozen-lockfile

# Switch back to root to copy scripts and set permissions
USER root

RUN chmod +x /usr/local/bin/myddleware-*.sh
CMD ["myddleware-foreground.sh"]
