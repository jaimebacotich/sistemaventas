#!/bin/bash

# Script de Despliegue Atómico para el Runner
set -e
set -x # Habilitar modo debug para ver errores detallados

echo "🚀 Iniciando despliegue atómico..."
echo "👤 Usuario: $(whoami)"
echo "📂 Ruta actual: $(pwd)"
echo "🛠️ PHP: $(php -v | head -n 1)"
echo "🛠️ Composer: $(composer -V 2>/dev/null || echo 'No encontrado')"

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

# 2.1 Asegurar estructura de storage (Crucial para Laravel)
echo "📁 Verificando estructura de storage compartido..."
mkdir -p "$SHARED_PATH/storage/app/public"
mkdir -p "$SHARED_PATH/storage/framework/cache/data"
mkdir -p "$SHARED_PATH/storage/framework/sessions"
mkdir -p "$SHARED_PATH/storage/framework/testing"
mkdir -p "$SHARED_PATH/storage/framework/views"
mkdir -p "$SHARED_PATH/storage/logs"
chmod -R 775 "$SHARED_PATH/storage" || true # Intentar ajustar permisos, ignorar si falla

# 3. Optimización de Laravel
echo "🧹 Ejecutando tareas de mantenimiento en el release..."
cd "$RELEASE_PATH"

# Permisos previos (Solo en carpetas propias del release)
echo "📂 Creando directorio bootstrap/cache si no existe..."
mkdir -p bootstrap/cache
echo "🔒 Ajustando permisos de bootstrap/cache..."
chmod -R 775 bootstrap/cache

echo "🔍 Verificando archivo .env..."
if [ ! -f .env ]; then
    echo "❌ ERROR: No se encuentra el archivo .env"
    ls -la
    exit 1
fi

echo "🎼 Regenerando autoloader de Composer..."
composer dump-autoload --optimize --classmap-authoritative

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
