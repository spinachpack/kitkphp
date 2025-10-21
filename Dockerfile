# Use official PHP image with Apache
FROM php:8.2-apache

# Install MySQLi extension
RUN docker-php-ext-install mysqli

# Enable URL rewriting for .htaccess
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files into container
COPY . /var/www/html/

# Create upload folders and set proper permissions
RUN mkdir -p uploads/profiles uploads/equipment \
    && chown -R www-data:www-data uploads \
    && chmod -R 755 uploads

# Expose web server port
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
