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

RUN docker-php-ext-install pdo pdo_mysql bcmath intl zip

##############################
# 1) Stage: Build everything
##############################

FROM base AS build

# Install nodejs and npm
RUN apt-get update && apt-get install -y nodejs npm

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

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

# Run Apache in the foreground
CMD ["apache2-foreground"]