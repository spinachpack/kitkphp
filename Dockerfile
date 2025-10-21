# Use the official PHP + Apache image
FROM php:8.2-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite if your app needs clean URLs
RUN a2enmod rewrite

# Copy all app files into the web root
COPY . /var/www/html/

# Set proper permissions for uploads
RUN mkdir -p /var/www/html/uploads/profiles /var/www/html/uploads/equipment \
    && chmod -R 777 /var/www/html/uploads

# Expose port 80 for Render
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
