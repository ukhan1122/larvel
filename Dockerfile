FROM richarvey/nginx-php-fpm:3.1.6
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN php artisan migrate --force || true
RUN php artisan storage:link
RUN php artisan config:clear
RUN php artisan config:cache
RUN php artisan route:clear
RUN php artisan view:clear

# Nuclear option - clear absolutely everything
RUN rm -f bootstrap/cache/*.php
RUN php artisan config:clear
RUN php artisan route:clear
RUN php artisan cache:clear
RUN php artisan view:clear
RUN php artisan optimize:clear

# Image config
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


# Override nginx configuration to ensure correct document root
COPY nginx-site.conf /etc/nginx/sites-available/default.conf
RUN ln -sf /etc/nginx/sites-available/default.conf /etc/nginx/sites-enabled/default.conf
CMD ["/start.sh"]