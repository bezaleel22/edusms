#!/bin/sh
echo "Starting services..."
service php8.1-fpm start
nginx -g "daemon off;" &
echo "Ready."
tail -s 1 /var/log/nginx/*.log -f | grep --color -P 'error|'
