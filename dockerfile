
FROM php:8.2-apache

# Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mysqli gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure Apache ServerName
RUN printf '%s\n' 'ServerName localhost' \
    > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# Remove conflicting Apache MPM modules
RUN find /etc/apache2/mods-enabled -maxdepth 1 \
    \( -name 'mpm_*.load' -o -name 'mpm_*.conf' \) \
    -delete \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Verify Apache configuration and MPM
RUN apache2ctl -t \
    && test "$(apache2ctl -M 2>/dev/null | grep -Ec 'mpm_(event|worker|prefork)_module')" -eq 1

# Set project directory
WORKDIR /var/www/html

# Copy Bantay-Dagat project
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose Apache port
EXPOSE 80

# Ensure only prefork MPM is enabled before startup
CMD ["bash", "-c", "a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true; rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*; a2enmod mpm_prefork >/dev/null; apache2ctl -t && exec apache2-foreground"]
