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
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 2. Habilitar mod_rewrite
RUN a2enmod rewrite

# 3. Definir diretório de trabalho
WORKDIR /var/www/html

# Em vez de COPY . /var/www/html (que copia a pasta back-end inteira)
COPY back-end /var/www/html

# 4. Instalar Composer (Agora vai achar o composer.json na raiz certa)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 5. Permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Dar permissão de execução e corrigir quebra de linha (Windows -> Linux)
RUN chmod +x /var/www/html/start.sh && sed -i 's/\r$//' /var/www/html/start.sh

# 6. Configuração do Apache (Força Bruta - Cria o arquivo do zero)
# Garante que AllowOverride All esteja ativado para ler o .htaccess
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

# 7. Expor porta 80
EXPOSE 80

# 8. Script de inicialização
# ATENÇÃO: Se o start.sh estiver na raiz do repo, usamos COPY start.sh
# Se estiver dentro de back-end, ele já foi copiado no passo 3.
# Vou assumir que você criou ele na RAIZ do repo junto com o Dockerfile:
COPY start.sh /var/www/html/start.sh
RUN chmod +x /var/www/html/start.sh && sed -i 's/\r$//' /var/www/html/start.sh

CMD ["/var/www/html/start.sh"]