<?php
/**
 * Router para o servidor embutido do PHP (CLI server).
 *
 * Como usar:
 *   php -S 127.0.0.1:8080 -t public router.php
 *
 * - Entrega arquivos estáticos diretamente (CSS/JS/imagens) a partir de public/.
 * - Mapeia a rota "/channels" (sem .php) para o endpoint real em api/channels.php.
 * - Mantém compatibilidade com "/channels.php".
 * - Para qualquer outra URL, faz fallback para a UI (public/index.php).
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$docroot = __DIR__ . '/public';

/**
 * Se o caminho solicitado aponta para um arquivo real dentro de public/,
 * devolve false para o CLI server servir o arquivo diretamente.
 */
$full = realpath($docroot . $uri);
if ($full && is_file($full)) {
    return false;
}

/**
 * Mantém query string e lógica de headers do endpoint.
 */
if ($uri === '/channels' || $uri === '/channels/') {
    require __DIR__ . '/api/channels.php';
    return true;
}

/**
 * Se o cliente pedir /channels.php, direciona para o mesmo endpoint.
 */
if ($uri === '/channels.php') {
    require __DIR__ . '/api/channels.php';
    return true;
}

/**
 * Fallback para a UI (index.php).
 */
require $docroot . '/index.php';
