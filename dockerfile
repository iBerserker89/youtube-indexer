# syntax=docker/dockerfile:1

# =========================
# Etapa de dependências
# =========================
FROM composer:2 AS deps
WORKDIR /app

# Copia manifestos do Composer e resolve deps (sem dev)
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# ==============
# Runtime
# ==============
FROM php:8.3-cli-alpine
WORKDIR /app

# Copia o código da aplicação
COPY . .

# Copia vendor resolvido na etapa "deps"
COPY --from=deps /app/vendor /app/vendor

# Garante a pasta de cache
RUN mkdir -p storage/cache

ENV PORT=10000
EXPOSE 10000

# Servidor embutido do PHP com router
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public router.php"]
