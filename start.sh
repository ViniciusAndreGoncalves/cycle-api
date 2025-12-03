#!/bin/bash

echo "Iniciando Deploy..."

# 1. Rodar Migrations
echo "Rodando Migrations..."
php artisan migrate --force

# 2. Rodar Seeders (Garante que a tabela de categorias existe)
echo "Rodando Seeders..."
php artisan db:seed --class=CategoriaAtivosSeeder --force

# 3. Limpar Tokens do Sanctum
echo "Limpando tokens expirados..."
php artisan sanctum:prune-expired

# 4. Iniciar o Apache (Isso mantém o servidor rodando)
echo "Iniciando Servidor Web..."
apache2-foreground