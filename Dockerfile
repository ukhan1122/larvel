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

# Create nginx conf.d directory and add config
RUN mkdir -p /etc/nginx/conf.d && \
    echo 'server { \
    listen 80; \
    server_name _; \
    root /var/www/html/public; \
    \
    index index.php index.html; \
    \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    \
    location ~ \.php$ { \
        try_files $uri =404; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; \
        fastcgi_param DOCUMENT_ROOT $realpath_root; \
    } \
    \
    location ~ /\.ht { \
        deny all; \
    } \
}' > /etc/nginx/conf.d/default.conf

# Image config
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1
ENV COMPOSER_ALLOW_SUPERUSER 1

CMD ["/start.sh"]