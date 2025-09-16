# Dockerfile
FROM php:8.2-cli-alpine
WORKDIR /app
COPY . /app
RUN mkdir -p storage/cache
EXPOSE 8080
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public router.php"]