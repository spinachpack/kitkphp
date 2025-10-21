# Use the official PHP + Apache image
FROM php:8.2-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite if needed
RUN a2enmod rewrite

# Copy app files
COPY . /var/www/html/

# Make sure the Apache user owns the app directory
RUN chown -R www-data:www-data /var/www/html

# Create upload directories and ensure writable permissions
RUN mkdir -p /var/www/html/uploads/profiles /var/www/html/uploads/equipment && \
    chmod -R 775 /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/uploads

# Expose port 80 for Render
EXPOSE 80

# Run Apache in the foreground
CMD ["apache2-foreground"]
