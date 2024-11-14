FROM php:8.3-apache

RUN apt-get update && apt-get install -y git unzip curl sqlite3 libonig-dev libzip-dev libicu-dev
RUN docker-php-ext-install pdo pdo_mysql bcmath intl zip

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
COPY . .
RUN npm install && npm run build
RUN mkdir -p /var/www/html/storage/framework/cache/data && mkdir -p /var/www/html/bootstrap/cache
RUN sed -i 's/Listen 80/Listen 0.0.0.0:80/' /etc/apache2/ports.conf
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
EXPOSE 80

ENV DB_CONNECTION=sqlite \
    DB_HOST=localhost \
    DB_PORT= \
    DB_DATABASE=/var/www/html/storage/opengrc.sqlite \
    DB_USERNAME= \
    DB_PASSWORD=

RUN cp .env.example .env && \
    echo "DB_CONNECTION=${DB_CONNECTION}" >> .env && \
    echo "DB_HOST=${DB_HOST}" >> .env && \
    echo "DB_PORT=${DB_PORT}" >> .env && \
    echo "DB_DATABASE=${DB_DATABASE}" >> .env && \
    echo "DB_USERNAME=${DB_USERNAME}" >> .env && \
    echo "DB_PASSWORD=${DB_PASSWORD}" >> .env

RUN git config --global --add safe.directory /var/www/html
RUN touch /var/www/html/storage/opengrc.sqlite
RUN composer install --no-interaction --optimize-autoloader --no-dev
RUN php artisan key:generate
RUN php artisan migrate
RUN php artisan db:seed
ENV APP_ENV=local \
    APP_DEBUG=false
RUN echo "APP_ENV=${APP_ENV}" >> .env && \
    echo "APP_DEBUG=${APP_DEBUG}" >> .env
RUN php artisan config:clear

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
RUN a2enmod rewrite
CMD ["apache2-foreground"]