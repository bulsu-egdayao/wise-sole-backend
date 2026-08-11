FROM richarvey/nginx-php-fpm:3.1.6

COPY . .

ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

RUN sed -i 's/\r$//' /var/www/html/scripts/00-laravel-deploy.sh && \
    chmod +x /var/www/html/scripts/00-laravel-deploy.sh

CMD ["/start.sh"]