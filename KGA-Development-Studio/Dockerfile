# Imagen base de PHP con Apache
FROM php:8.2-apache

# Instalar extensiones para conectar PHP con MySQL
RUN docker-php-ext-install pdo pdo_mysql
RUN echo "upload_max_filesize=10M" > /usr/local/etc/php/conf.d/uploads.ini \
   && echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/uploads.ini
# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar el proyecto
COPY . .

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Crear carpeta de documentos y asignar permisos
RUN mkdir -p /var/www/html/API/uploads/documentos \
    && chown -R www-data:www-data /var/www/html/API/uploads \
    && chmod -R 755 /var/www/html/API/uploads

# Asignar propietario al proyecto
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

