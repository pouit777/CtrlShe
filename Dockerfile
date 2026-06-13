# Dockerfile
FROM php:8.2-apache

# Installation des extensions PHP nécessaires pour MySQL (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Activation du module de réécriture d'Apache (utile pour du routage propre / MVC)
RUN a2enmod rewrite

# Ajustement des permissions pour le serveur web
RUN chown -R www-data:www-data /var/www/html

# Définition du répertoire de travail
WORKDIR /var/www/html

EXPOSE 80
