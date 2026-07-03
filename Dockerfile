# Utilise une image PHP officielle avec Apache
FROM php:8.1-apache

# Installe les extensions PHP nécessaires (pdo_mysql, mysqli, etc.)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Active le module Apache mod_rewrite (utile si tu as un .htaccess)
RUN a2enmod rewrite

# Optionnel : copier tout le code dans l'image (pour la production)
# COPY . /var/www/html/

# Définit le répertoire de travail
WORKDIR /var/www/html

# Donne les droits d'écriture sur le dossier uploads (si utilisé)
RUN mkdir -p /var/www/html/assets/uploads && chown -R www-data:www-data /var/www/html/assets/uploads