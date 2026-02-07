#!/bin/bash

set -e

echo "🚀 Iniciando despliegue de Chat API..."

cd /var/www/chat-api

# Mantener variables de entorno
cp .env.production .env

# Pull latest changes
git pull origin main

# Limpiar caché
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Permisos
chown -R www-data:www-data /var/www/chat-api
chmod -R 755 /var/www/chat-api
chmod -R 775 /var/www/chat-api/storage
chmod -R 775 /var/www/chat-api/bootstrap/cache

# Reiniciar servicios
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

echo "✅ Despliegue completado!"
echo "📊 Servicios:"
sudo supervisorctl status
echo "🌐 URL: https://chat.maximoph.co"