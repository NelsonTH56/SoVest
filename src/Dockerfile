# Use PHP with Apache
FROM php:8.2-apache

# Install system dependencies and Node.js 20
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copy package.json and install Node dependencies (clean install for Linux)
COPY package.json package-lock.json ./
RUN npm ci

# Copy the rest of the application
COPY . .

# Run composer scripts now that all files are present
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configure Apache to use PORT env variable (Render sets this)
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Cache routes and views (NOT config - needs runtime env vars)
RUN php artisan route:cache \
    && php artisan view:cache

# Expose port (Render uses PORT env var, typically 10000)
EXPOSE 10000

# Start script to handle dynamic port and run migrations
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
