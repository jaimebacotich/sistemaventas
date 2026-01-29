#!/bin/bash

# Script de Despliegue Atómico para el Runner
set -e
set -x # Habilitar modo debug para ver errores detallados

echo "🚀 Iniciando despliegue atómico..."

# Configuración de Rutas
PROJECT_ROOT="/var/www/comprasventas"
RELEASE_ID=$(cat RELEASE_ID | head -n 1)
RELEASE_PATH="$PROJECT_ROOT/releases/$RELEASE_ID"
SHARED_PATH="$PROJECT_ROOT/shared"

echo "📂 Preparando carpeta de release: $RELEASE_ID"
mkdir -p "$RELEASE_PATH"

# 1. Copiar archivos del artefacto (ya descargados por el runner) a la carpeta de release
echo "📦 Desempaquetando artefacto..."
cp -rv . "$RELEASE_PATH/"

# 2. Enlazar archivos compartidos (Secrets y Storage)
echo "🔗 Enlazando recursos compartidos..."
ln -sfn "$SHARED_PATH/.env" "$RELEASE_PATH/.env"
rm -rf "$RELEASE_PATH/storage"
ln -sfn "$SHARED_PATH/storage" "$RELEASE_PATH/storage"

# 3. Optimización de Laravel
echo "🧹 Ejecutando tareas de mantenimiento en el release..."
cd "$RELEASE_PATH"

# Permisos previos
chmod -R 775 storage bootstrap/cache

php artisan optimize:clear
php artisan migrate --force
php artisan optimize

# 4. SWITCH ATÓMICO (EL MOMENTO CLAVE)
echo "🔄 Realizando cambio atómico de symlink..."
ln -sfn "$RELEASE_PATH" "$PROJECT_ROOT/current.new"
mv -Tf "$PROJECT_ROOT/current.new" "$PROJECT_ROOT/current"

# 5. Limpieza de versiones antiguas (Mantener solo las últimas 3)
echo "🧹 Limpiando releases antiguos..."
cd "$PROJECT_ROOT/releases"
ls -1t | tail -n +4 | xargs -r rm -rf

echo "✅ ¡Despliegue completado con éxito! Versión: $RELEASE_ID"
