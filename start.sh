#!/bin/bash

echo "Iniciando Deploy..."

echo "Atualizando autoloader..."
composer dump-autoload --optimize

# Rodar Migrations
echo "Rodando Migrations..."
php artisan migrate --force

php artisan route:clear
php artisan config:clear
php artisan config:cache
php artisan optimize

# Rodar Seeders (Garante que a tabela de categorias existe)
echo "Rodando Seeders..."
php artisan db:seed --class=AssetSeeder --force

# Iniciar o Apache (Isso mantém o servidor rodando)
echo "Iniciando Servidor Web..."
apache2-foreground