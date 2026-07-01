web: heroku-php-apache2 public/
release: php artisan migrate --force && php artisan storage:link || true
worker: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
scheduler: php artisan schedule:work
