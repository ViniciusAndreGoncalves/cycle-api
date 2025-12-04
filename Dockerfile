FROM php:8.2-apache

# 1. Instalação de dependências e driver MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql exif pcntl bcmath gd

# 2. Habilitar mod_rewrite
RUN a2enmod rewrite

# 3. Diretório de trabalho
WORKDIR /var/www/html

# 4. Instalar Dependências (Estratégia de Cache)
COPY composer.json composer.lock ./

# Instala sem rodar scripts (pois o código do app ainda não existe)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# 5. Copia o código fonte (O resto da aplicação)
COPY . .

# autoload final (com os scripts do Laravel)
RUN composer dump-autoload --optimize

# 6. Configuração do Apache
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# 7. Permissões e Ajustes Finais
# Ajusta permissões de pastas do Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Vacina contra Windows (Corrige quebra de linha \r e dá permissão de execução)
# O arquivo start.sh já foi copiado no passo 5 (COPY . .)
RUN chmod +x /var/www/html/start.sh && sed -i 's/\r$//' /var/www/html/start.sh

# 8. Expor porta e definir comando
EXPOSE 80

CMD ["/var/www/html/start.sh"]
    
# Dar permissão de execução e corrigir quebra de linha (Windows -> Linux)
# RUN chmod +x /var/www/html/start.sh && sed -i 's/\r$//' /var/www/html/start.sh