FROM php:8.2-apache

RUN printf '%s\n' \
    'display_errors=Off' \
    'display_startup_errors=Off' \
    'log_errors=On' \
    'error_log=/proc/self/fd/2' \
    'error_reporting=E_ALL' \
    'session.auto_start=0' \
    > /usr/local/etc/php/conf.d/bantay-dagat.ini

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY docker-entrypoint.sh /usr/local/bin/bantay-dagat-entrypoint
RUN chmod +x /usr/local/bin/bantay-dagat-entrypoint

WORKDIR /var/www/html

COPY . /var/www/html

RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 8080

CMD ["/usr/local/bin/bantay-dagat-entrypoint"]
