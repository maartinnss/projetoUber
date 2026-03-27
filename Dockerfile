FROM php:8.3-apache

# Step 1: Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

# Step 2: Enable Apache mod_rewrite
RUN a2enmod rewrite

# Step 3: Configure Apache DocumentRoot to serve the 'public' folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Step 4: Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Step 5: Copy Backend files
COPY backend/ .

# Step 6: Copy Frontend files into the public directory
# This merges your HTML/JS/CSS with the PHP entry point, just like Nginx did.
COPY frontend/ public/

# Step 7: Install PHP dependencies
RUN if [ -f "composer.json" ]; then \
    composer install --no-dev --optimize-autoloader; \
    fi

# Step 8: Set permissions
RUN chown -R www-data:www-data /var/www/html

COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["entrypoint.sh"]
