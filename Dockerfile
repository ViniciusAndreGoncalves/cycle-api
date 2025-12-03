FROM php:8.2-apache

# 1. Instalar dependências do sistema e extensões PHP necessárias (incluindo pgsql)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    libpq-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 2. Habilitar mod_rewrite do Apache para rotas do Laravel
RUN a2enmod rewrite

# 3. Definir diretório de trabalho
WORKDIR /var/www/html

# 4. Copiar arquivos do projeto
COPY . /var/www/html

# 5. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Instalar dependências do Laravel
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 7. Ajustar permissões (Crítico para Laravel não dar erro 500)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Configurar Apache para apontar para a pasta public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 9. Expor porta 80
EXPOSE 80

# 10. Copiar o script de inicialização
COPY start.sh /var/www/html/start.sh

# 11. Dar permissão de execução e corrigir quebra de linha (Windows -> Linux)
RUN chmod +x /var/www/html/start.sh && sed -i 's/\r$//' /var/www/html/start.sh

# 12. Definir o comando padrão para rodar o script
CMD ["/var/www/html/start.sh"]