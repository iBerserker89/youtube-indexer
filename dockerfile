# Dockerfile
FROM php:8.2-cli-alpine
WORKDIR /app
COPY . /app
RUN mkdir -p storage/cache
EXPOSE 8080
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public router.php"]# syntax=docker/dockerfile:1

# Etapa de dependências
FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# Runtime
FROM php:8.3-cli-alpine
WORKDIR /app

# Copia código-fonte
COPY . .

# Copia o vendor resolvido na etapa deps
COPY --from=deps /app/vendor /app/vendor

# Pasta de cache
RUN mkdir -p storage/cache

# Render usa PORT=10000 por padrão;
ENV PORT=10000
EXPOSE 10000

# Servidor embutido do PHP + router
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public router.php"]
