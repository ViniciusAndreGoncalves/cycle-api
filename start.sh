#!/bin/bash

echo "Iniciando Deploy..."

# Limpar caches antigos
echo "🧹 Limpando caches antigos..."
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

php artisan route:cache

# Rodar Migrations
echo "Rodando Migrations..."
php artisan migrate --force

# Rodar Seeders (Garante que a tabela de categorias existe)
echo "Rodando Seeders..."
php artisan db:seed --class=AssetSeeder --force

# Limpar Tokens do Sanctum
echo "Limpando tokens expirados..."
php artisan sanctum:prune-expired

# Iniciar o Apache (Isso mantém o servidor rodando)
echo "Iniciando Servidor Web..."
apache2-foreground