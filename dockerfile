
FROM php:8.2-apache

# Install PDO MySQL and MySQLi extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache URL rewriting
RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Configure Apache for Railway's assigned port
CMD ["bash", "-lc", "PORT=${PORT:-8080}; sed -ri \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf; sed -ri \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/000-default.conf; exec apache2-foreground"]
