#!/bin/sh
set -e

# Ensure all logs go to stdout/stderr
rm -rf /var/www/html/storage/logs/*
ln -sf /dev/stdout /var/www/html/storage/logs/laravel.log

# Optimize Laravel as quickdrop user
su-exec quickdrop php /var/www/html/artisan optimize
su-exec quickdrop php /var/www/html/artisan config:cache
su-exec quickdrop php /var/www/html/artisan route:cache
su-exec quickdrop php /var/www/html/artisan view:cache

# Start supervisord (which will run processes as configured users)
exec /usr/bin/supervisord -n -c /etc/supervisor.d/supervisord.ini