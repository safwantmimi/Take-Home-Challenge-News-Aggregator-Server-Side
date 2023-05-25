# Use the official PHP 8.1 base image
FROM php:8.2.0-fpm

# Set the working directory in the container
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copy composer.lock and composer.json
COPY composer.lock composer.json /var/www/html/

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install application dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the application code
COPY . /var/www/html

# Generate the optimized autoloader
RUN composer dump-autoload --optimize

# Set the appropriate permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 9000 and start the PHP-FPM server
EXPOSE 9000
CMD ["php-fpm"]


# Scheduled news fetching

# Install cron
RUN apt-get update && apt-get -y install cron

# Add crontab file
ADD crontab /etc/cron.d/laravel-scheduler

# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/laravel-scheduler

# Apply cron job
RUN crontab /etc/cron.d/laravel-scheduler

# Run the command on container startup
CMD cron && docker-php-entrypoint php-fpm
