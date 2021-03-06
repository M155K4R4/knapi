#!/usr/bin/env sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- bin/server "$@"
fi

if [ "$1" = 'bin/server' ] || [ "$1" = 'bin/console' ]; then
    echo "NGINX_PORT=$PORT"
    sed -i "s#NGINX_PORT#$PORT#g" /etc/nginx/conf.d/default.conf
    nginx
	docker-app-bootstrap
fi

exec docker-php-entrypoint "$@"
