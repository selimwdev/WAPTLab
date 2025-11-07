#!/usr/bin/env bash
set -e

cd /var/www/html

# نسخ .env إذا مش موجود
if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

# تثبيت PHP deps
composer install --no-interaction --prefer-dist || true

# انتظر MySQL قبل الميجريشنز
until php -r "new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
  echo "Waiting for MySQL..."
  sleep 3
done

# Laravel setup
php artisan key:generate --force || true
echo "🏗️ Migrating all databases..."
php artisan migrate --force --database=mysql || true
php artisan migrate --force --database=mysql_hr || true
php artisan migrate --force --database=mysql_support || true
php artisan migrate --force --database=mysql_admin|| true

# تثبيت Node deps
npm install --no-audit --no-fund || true

# Build frontend assets (Vite)
echo "Building frontend assets..."
npx vite build --outDir public/build || true

# 🧠 انتظر Elasticsearch يكون جاهز قبل ما تبدأ snapshot
echo "⏳ Waiting for Elasticsearch..."
until curl -s http://elasticsearch:9200 >/dev/null 2>&1; do
  echo "Elasticsearch not ready yet..."
  sleep 3
done

# 🕐 انتظر شوية عشان تتأكد كل حاجة اشتغلت
echo "⏱️ Waiting 10 seconds before seeding..."
sleep 10

# 🌱 شغل Seeder
echo "🌱 Running MultiConnectionSeeder..."
php artisan db:seed --class=MultiConnectionSeeder || echo "⚠️ seeder failed (ignored)"
php artisan db:seed --class=OauthClientsSeeder

# 📸 شغّل snapshot أول مرة بعد السييد
echo "📸 Running initial Elasticsearch snapshot..."
php artisan es:snapshot || echo "⚠️ snapshot failed (ignored)"

# 🧠 شغّل es:index-eav كل دقيقة في background
(
  echo "🔁 Starting background Elasticsearch indexer..."
  while true; do
    php artisan es:index-eav || echo "⚠️ index-eav failed"
    sleep 60
  done
) &

# تشغيل Supervisord (Laravel + أي services إضافية)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
