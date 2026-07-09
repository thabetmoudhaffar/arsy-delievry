FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        zip \
        unzip \
        libonig-dev \
        libxml2-dev \
        libssl-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mysqli opcache \
    && a2enmod rewrite

# Copy application
COPY . /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && mkdir -p /var/www/html/storage /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]
