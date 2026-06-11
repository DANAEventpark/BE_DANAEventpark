FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev
RUN npm install
RUN npm run build

# Copy Nginx config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Setup permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Make start script executable
RUN chmod +x docker/start.sh

# Expose port
EXPOSE 80

# Start services
CMD ["/var/www/docker/start.sh"]
