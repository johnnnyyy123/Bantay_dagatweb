
FROM php:8.2-apache

# Configure PHP error handling
RUN printf '%s\n' \
    'display_errors=Off' \
    'display_startup_errors=Off' \
    'log_errors=On' \
    'error_reporting=E_ALL' \
    'output_buffering=4096' \
    'session.auto_start=0' \
    > /usr/local/etc/php/conf.d/bantay-dagat.ini

# Verify PHP configuration
RUN php --ini && php -i | grep -E \
    'display_errors|display_startup_errors|log_errors|session.auto_start'
# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        gd \
        curl \
        mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*



# Configure Apache ServerName
RUN printf '%s\n' 'ServerName localhost' \
    > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# Configure Apache modules
RUN find /etc/apache2/mods-enabled -maxdepth 1 \
    \( -name 'mpm_*.load' -o -name 'mpm_*.conf' \) \
    -delete \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Verify Apache configuration
RUN apache2ctl -t \
    && test "$(apache2ctl -M 2>/dev/null | grep -Ec 'mpm_(event|worker|prefork)_module')" -eq 1

# Set project directory
WORKDIR /var/www/html

# Copy project
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose Apache port
EXPOSE 80

# Start Apache
CMD ["bash", "-c", "a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true; rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*; a2enmod mpm_prefork >/dev/null; apache2ctl -t && exec apache2-foreground"]
