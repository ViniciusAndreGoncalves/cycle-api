FROM php:8.2-apache

# 1. Instalação de dependências e drivers (MySQL E PostgreSQL)
# Adicionei libpq-dev e pdo_pgsql para garantir compatibilidade no Render
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo_mysql pdo_pgsql exif pcntl bcmath gd

# 2. Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# 3. Diretório de trabalho
WORKDIR /var/www/html

# 4. Instalar Composer e Dependências
COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# 5. Copia todo o código fonte
COPY . .

# 6. Gera o autoloader otimizado (Garante que as classes novas sejam vistas)
RUN composer dump-autoload --optimize

# 7. Configuração do VHost Apache (Direto no arquivo para evitar erros de cópia)
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

# 8. CORREÇÃO CRÍTICA DE PERMISSÕES E WINDOWS
# Contra quebras de linha do Windows (\r\n -> \n) no script
RUN sed -i 's/\r$//' /var/www/html/start.sh

# Torna o script executável
RUN chmod +x /var/www/html/start.sh

# Passa a posse de TUDO para o usuário do Apache (www-data)
# Isso permite que o "php artisan optimize" funcione no start.sh sem erro de permissão
RUN chown -R www-data:www-data /var/www/html

# 9. Expor porta e definir comando
EXPOSE 80

CMD ["/var/www/html/start.sh"]
    
# Dar permissão de execução e corrigir quebra de linha (Windows -> Linux)
# RUN chmod +x /var/www/html/start.sh && sed -i 's/\r$//' /var/www/html/start.sh