FROM php:8.4-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY . /var/www/html/
COPY docker/start-apache.sh /usr/local/bin/start-apache

RUN chmod +x /usr/local/bin/start-apache \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 10000

CMD ["start-apache"]
