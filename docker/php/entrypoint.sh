#!/bin/sh
set -e

if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

echo "Waiting for database connection..."
until mysqladmin --ssl=0 ping -h"${DB_HOST:-mariadb}" -u"${DB_USER:-codesign}" -p"${DB_PASSWORD:-codesign}" --silent 2>/dev/null; do
    sleep 2
done
echo "Database is ready!"

echo "Checking if database exists..."
php -r "
try {
    new PDO('mysql:host=${DB_HOST:-mariadb};port=${DB_PORT:-3306}', '${DB_USER:-codesign}', '${DB_PASSWORD:-codesign}');
    echo 'Database connection OK\n';
} catch (PDOException \$e) {
    if (str_contains(\$e->getMessage(), 'Unknown database')) {
        echo 'Database does not exist, creating...\n';
        \$pdo = new PDO('mysql:host=${DB_HOST:-mariadb};port=${DB_PORT:-3306}', 'root', '${DB_ROOT_PASSWORD:-rootpassword}');
        \$pdo->exec('CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE:-codesign}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        echo 'Database created!\n';
    } else {
        throw \$e;
    }
}
"

MIGRATION_MARKER="/var/www/html/storage/.migrations_completed"

if [ -f /var/www/html/bin/kernel ] && [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    if [ ! -f "$MIGRATION_MARKER" ]; then
        echo "Running Doctrine migrations..."
        php /var/www/html/bin/kernel orm:schema-tool:create || true
        php /var/www/html/bin/kernel init || true
        touch "$MIGRATION_MARKER"
        echo "Initialization & Migration completed!"
    else
        echo "Migrations already completed, skipping..."
    fi
fi

exec docker-php-entrypoint "$@"