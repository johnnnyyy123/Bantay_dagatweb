
FROM php:8.2-apache

# Install required dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Remove enabled MPM configurations
RUN find /etc/apache2/mods-enabled -maxdepth 1 \
    \( -name 'mpm_*.load' -o -name 'mpm_*.conf' \) \
    -delete \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Verify enabled MPM modules
RUN apache2ctl -M | grep mpm_ \
    && test "$(apache2ctl -M | grep -c mpm_)" -eq 1

# Diagnose all Apache MPM loading directives
RUN echo "===== ENABLED MPM MODULES =====" \
    && ls -la /etc/apache2/mods-enabled/*mpm* 2>/dev/null || true

RUN echo "===== ALL MPM LOAD DIRECTIVES =====" \
    && find -L /etc/apache2 -type f \
    \( -name "*.load" -o -name "*.conf" \) \
    -exec grep -HnE '^[[:space:]]*LoadModule[[:space:]]+mpm_' {} + || true

# Set project directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Railway port
EXPOSE 80

# Fix MPM modules before starting Apache
CMD ["bash", "-c", "a2dismod mpm_event mpm_worker || true; rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*; a2enmod mpm_prefork; apache2ctl -t && exec apache2-foreground"]
