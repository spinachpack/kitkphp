# Use official PHP image with Apache
FROM php:8.2-apache

# Install MySQLi extension (needed for $conn = new mysqli(...))
RUN docker-php-ext-install mysqli

# Enable URL rewriting (for .htaccess if needed)
RUN a2enmod rewrite

# Copy your project files to Apache's web directory
COPY . /var/www/html/

# Make upload directories writable by Apache
RUN mkdir -p /var/www/html/uploads/profiles /var/www/html/uploads/equipment \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

# Expose web server port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
