# Dockerfile
FROM php:8.2-apache

# Install necessary PHP extensions for MySQL connectivity (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module (essential for clean routing and MVC setups)
RUN a2enmod rewrite

# Configure Apache to prioritize index.php over other standard files
RUN echo "DirectoryIndex index.php login.php index.html" > /etc/apache2/mods-enabled/dir.conf

# Set appropriate directory permissions for the www-data web server user
RUN chown -R www-data:www-data /var/www/html

# Define the container's working directory
WORKDIR /var/www/html

EXPOSE 80
