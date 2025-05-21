#!/bin/bash

# Run migrations with :fresh if env var $DB_FRESH is set
if [ -n "$DB_FRESH" ]; then
  php /var/www/html/artisan migrate:fresh --force
  php /var/www/html/artisan db:seed --force
else
  php /var/www/html/artisan migrate --force
fi

# Start Apache
apache2-foreground
