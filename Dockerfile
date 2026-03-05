FROM richarvey/nginx-php-fpm:3.1.6
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN php artisan storage:link
RUN php artisan config:clear
RUN php artisan config:cache
RUN php artisan route:clear
RUN php artisan view:clear

# Force correct permissions
RUN chmod -R 777 storage bootstrap/cache

# CRITICAL: This is the correct Nginx configuration that actually works
RUN mkdir -p /etc/nginx/conf.d && \
    echo 'server { \
        listen 80; \
        root /var/www/html/public; \
        index index.php index.html; \
        \
        location / { \
            try_files $uri $uri/ /index.php?$query_string; \
        } \
        \
        location ~ \.php$ { \
            fastcgi_pass 127.0.0.1:9000; \
            fastcgi_index index.php; \
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
            include fastcgi_params; \
        } \
    }' > /etc/nginx/conf.d/default.conf

# Image config
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1
ENV COMPOSER_ALLOW_SUPERUSER 1
COPY nginx-laravel.conf /etc/nginx/conf.d/default.conf

CMD ["/start.sh"]