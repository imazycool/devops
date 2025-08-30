#!/bin/sh
# Start PHP-FPM in the background only if not already running
if ! pgrep -x "php-fpm" > /dev/null
then
    php-fpm -D
fi

# Start Nginx in foreground
nginx -g 'daemon off;'