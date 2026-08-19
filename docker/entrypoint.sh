#!/bin/sh
set -e

# Every service (app, queue, scheduler, reverb) runs from this exact image;
# only the final command differs. No build step ever runs here.
case "$1" in
    php-fpm)
        exec php-fpm
        ;;
    queue)
        exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600
        ;;
    scheduler)
        exec php artisan schedule:work
        ;;
    reverb)
        exec php artisan reverb:start --host=0.0.0.0
        ;;
    migrate)
        exec php artisan migrate --force
        ;;
    *)
        exec "$@"
        ;;
esac
