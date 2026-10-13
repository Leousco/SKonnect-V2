FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Install PHP dependencies inside the container build
RUN composer install --no-dev --optimize-autoloader
RUN mkdir -p /var/www/html/assets/uploads /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/assets/uploads /var/www/html/uploads

EXPOSE 80