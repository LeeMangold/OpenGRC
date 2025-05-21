FROM php:8.3-apache AS base

# --------------
# Install needed Debian/Ubuntu packages
# ------------------------------------------------
RUN apt-get clean && apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip 

RUN docker-php-ext-install pdo pdo_mysql bcmath intl zip gd

##############################
# 1) Stage: Build everything
##############################

FROM base AS build

# Install nodejs and npm

ENV NODE_VERSION=20.19.1

RUN apt-get update && apt-get install -y gnupg2

RUN ARCH= && dpkgArch="$(dpkg --print-architecture)" \
  && case "${dpkgArch##*-}" in \
    amd64) ARCH='x64';; \
    ppc64el) ARCH='ppc64le';; \
    s390x) ARCH='s390x';; \
    arm64) ARCH='arm64';; \
    armhf) ARCH='armv7l';; \
    i386) ARCH='x86';; \
    *) echo "unsupported architecture"; exit 1 ;; \
  esac \
  # use pre-existing gpg directory, see https://github.com/nodejs/docker-node/pull/1895#issuecomment-1550389150
  && export GNUPGHOME="$(mktemp -d)" \
  # gpg keys listed at https://github.com/nodejs/node#release-keys
  && set -ex \
  && for key in \
    C0D6248439F1D5604AAFFB4021D900FFDB233756 \
    DD792F5973C6DE52C432CBDAC77ABFA00DDBF2B7 \
    CC68F5A3106FF448322E48ED27F5E38D5B0A215F \
    8FCCA13FEF1D0C2E91008E09770F7A9A5AE15600 \
    890C08DB8579162FEE0DF9DB8BEAB4DFCF555EF4 \
    C82FA3AE1CBEDC6BE46B9360C43CEC45C17AB93C \
    108F52B48DB57BB0CC439B2997B01419BD92F80A \
    A363A499291CBBC940DD62E41F10027AF002F8B0 \
  ; do \
      gpg --batch --keyserver hkps://keys.openpgp.org --recv-keys "$key" || \
      gpg --batch --keyserver keyserver.ubuntu.com --recv-keys "$key" ; \
  done \
  && curl -fsSLO --compressed "https://nodejs.org/dist/v$NODE_VERSION/node-v$NODE_VERSION-linux-$ARCH.tar.xz" \
  && curl -fsSLO --compressed "https://nodejs.org/dist/v$NODE_VERSION/SHASUMS256.txt.asc" \
  && gpg --batch --decrypt --output SHASUMS256.txt SHASUMS256.txt.asc \
  && gpgconf --kill all \
  && rm -rf "$GNUPGHOME" \
  && grep " node-v$NODE_VERSION-linux-$ARCH.tar.xz\$" SHASUMS256.txt | sha256sum -c - \
  && tar -xJf "node-v$NODE_VERSION-linux-$ARCH.tar.xz" -C /usr/local --strip-components=1 --no-same-owner \
  && rm "node-v$NODE_VERSION-linux-$ARCH.tar.xz" SHASUMS256.txt.asc SHASUMS256.txt \
  && ln -s /usr/local/bin/node /usr/local/bin/nodejs \
  # smoke tests
  && node --version \
  && npm --version \
  && rm -rf /tmp/*

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

COPY composer.json composer.lock /var/www/html/

# Install Composer dependencies
RUN composer install --no-scripts

# Copy application code
COPY . .

# Install Composer dependencies (including dev dependencies) and run initial setup
RUN composer update && php artisan opengrc:install --unattended

########################################
# 2) Stage: Final - Production runtime
########################################
FROM base AS production

# Copy Composer binary (needed to remove dev dependencies and cache)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy entire Laravel app (including vendor) from build stage
COPY --from=build /var/www/html .

# Remove PHP development dependencies and clear Composer cache
RUN composer install --no-dev --optimize-autoloader && \
    composer clear-cache && \
    rm -rf /root/.composer/cache

# Remove node_modules
RUN rm -rf /var/www/html/node_modules

# Make sure storage and bootstrap/cache are writable
RUN mkdir -p storage/framework/cache/data bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache /var/www/html \
    && chmod -R 775 storage bootstrap/cache /var/www/html
    
# Ensure there's a sqlite file
RUN touch /var/www/html/database/opengrc.sqlite

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Listen on port 8080 instead of 80
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
EXPOSE 8080

# Update the default vhost to point to /var/www/html/public
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Replace the VirtualHost port in 000-default.conf
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides and full access
RUN echo "<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n" >> /etc/apache2/apache2.conf

# Set a server name
RUN echo "ServerName 0.0.0.0" >> /etc/apache2/apache2.conf

COPY ./entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

RUN mkdir -p /var/www/html/storage/framework/cache/data
RUN chown -R www-data:www-data /var/www/html/storage/framework/cache/data

USER www-data

# Use shell form instead of exec form to ensure proper execution
ENTRYPOINT ["/bin/bash", "-c", "/entrypoint.sh"]
