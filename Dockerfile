FROM php:8.2-fpm

# 🧱 تثبيت المتطلبات الأساسية
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libonig-dev libxml2-dev libxslt-dev \
    supervisor procps iputils-ping ca-certificates cron \
 && docker-php-ext-install pdo_mysql mbstring zip xml xsl \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# 🟢 تثبيت Node 20 و npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs

# 🟣 تثبيت Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

# 🧩 تثبيت PHP dependencies
RUN composer install --no-interaction --prefer-dist || true

# 🕒 إعداد الـ cron job لتشغيل es:index-eav كل دقيقة
RUN echo "* * * * * cd /var/www/html && php artisan es:index-eav >> /var/www/html/storage/logs/cron.log 2>&1" > /etc/cron.d/laravel-cron \
 && chmod 0644 /etc/cron.d/laravel-cron \
 && crontab /etc/cron.d/laravel-cron

# ⚙️ نسخ ملفات supervisor و entrypoint
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 📦 فتح البورتات
EXPOSE 8000 5173 5174

# 🚀 الأوامر عند بدء التشغيل:
# 1. تشغيل cron
# 2. تشغيل php artisan es:snapshot
# 3. تشغيل supervisor (اللي بيشغّل Laravel وغيره)
CMD service cron start && php artisan es:snapshot && exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
