FROM php:8.4-fpm

# ── Dependências de sistema ──────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libicu-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    intl \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Composer ──────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Node 22 (exigido pelo Vite 7) ────────────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

WORKDIR /app

# ── Instala dependências primeiro (cache de layer) ─────────────────────
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts

COPY package.json package-lock.json* ./
RUN npm install --no-audit --no-fund

# ── Copia o restante do código ──────────────────────────────────────────
COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && npm run build \
    && rm -rf node_modules

# ── Permissões ────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# ── Configs de Nginx, PHP-FPM, Supervisor ──────────────────────────────
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/php-fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache-custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
