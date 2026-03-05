FROM richarvey/nginx-php-fpm:3.1.6
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN php artisan migrate --force || true
RUN php artisan storage:link
RUN php artisan config:clear
RUN php artisan config:cache
RUN php artisan route:clear
RUN php artisan view:clear
RUN php artisan optimize:clear

# Test PHP-FPM configuration
RUN php-fpm -t

# Image config - THESE ARE CRITICAL
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel config
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

CMD ["/start.sh"]