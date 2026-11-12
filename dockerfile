
FROM php:8.2-apache

# Install PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli gd \
    && rm -rf /var/lib/apt/lists/*

# Remove every enabled Apache MPM configuration
RUN find /etc/apache2/mods-enabled -maxdepth 1 \
    \( -name 'mpm_*.load' -o -name 'mpm_*.conf' \) \
    -delete \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Verify that exactly one MPM is enabled
RUN apache2ctl -M | grep mpm_ \
    && test "$(apache2ctl -M | grep -c mpm_)" -eq 1

# Project directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["bash", "-c", "a2dismod mpm_event mpm_worker || true; rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*; a2enmod mpm_prefork; apache2ctl -t && exec apache2-foreground"]
