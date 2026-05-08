#!/bin/bash
# Скрипт деплоя АвтоЗапчасть
# Запускать на сервере: bash /var/www/html/deploy.sh

set -e

REPO_DIR="/var/www/html"
BRANCH="main"
DB_NAME="avtozapchast"
DB_USER="root"
DB_PASS=""

echo "==> Обновление кода с GitHub..."
cd "$REPO_DIR"
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

echo "==> Создание базы данных (если не существует)..."
mysql -u"$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true

echo "==> Применение схемы БД..."
mysql -u"$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" < "$REPO_DIR/sql/schema.sql"

echo "==> Создание папки uploads..."
mkdir -p "$REPO_DIR/assets/uploads"
chown -R www-data:www-data "$REPO_DIR/assets/uploads"
chmod -R 755 "$REPO_DIR/assets/uploads"

echo "==> Перезагрузка PHP-FPM..."
systemctl reload php8.2-fpm

echo "==> Готово! Сайт обновлён."
