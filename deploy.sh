#!/bin/bash

echo "🚀 Iniciando despliegue..."

# Obtener últimos cambios del repositorio
echo "📥 Ejecutando git pull..."
git pull

# Limpiar configuraciones y cachés
echo "🧹 Limpiando cachés de Laravel..."
php artisan optimize:clear

# Reconstruir la caché de configuración
echo "📦 Generando config:cache..."
php artisan config:cache

echo "✅ Despliegue completado."


echo "📦 Generando config:cache..."