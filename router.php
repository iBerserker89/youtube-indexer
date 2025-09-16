<?php
/**
 * Router para o servidor embutido do PHP (CLI server).
 *
 * Como usar:
 *   php -S 127.0.0.1:8080 -t public router.php
 *
 * O que ele faz:
 * - Entrega arquivos estáticos diretamente (CSS/JS/imagens) a partir de public/.
 * - Mapeia a rota "/channels" (sem .php) para o endpoint real em api/channels.php.
 * - Mantém compatibilidade com "/channels.php".
 * - Para qualquer outra URL, faz fallback para a UI (public/index.php).
 *
 * Por que existe:
 * - O servidor embutido do PHP ignora .htaccess (não há mod_rewrite).
 * - Este roteador permite URL “bonita” (/channels) sem depender de Apache.
 *
 * Observações:
 * - A query string é preservada automaticamente ao incluir api/channels.php.
 * - Se o seu app estiver em subpasta, o -t public já resolve o docroot; use os
 *   caminhos relativos como estão (baseados em __DIR__).
 * - Em produção com Apache/cPanel, prefira usar public/.htaccess (ou chame /channels.php direto).
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$docroot = __DIR__ . '/public';

/**
 * 1) Arquivos estáticos:
 * Se o caminho solicitado aponta para um arquivo real dentro de public/,
 * devolvemos false para o CLI server servir o arquivo diretamente.
 */
$full = realpath($docroot . $uri);
if ($full && is_file($full)) {
    return false; // deixa o servidor embutido servir o arquivo estático
}

/**
 * 2) API sem ".php":
 * Rota “bonita” /channels → endpoint real fora do docroot (api/channels.php).
 * Mantém query string e lógica de headers do endpoint.
 */
if ($uri === '/channels' || $uri === '/channels/') {
    require __DIR__ . '/api/channels.php';
    return true;
}

/**
 * 3) Compatibilidade:
 * Se o cliente pedir /channels.php, também direcionamos para o mesmo endpoint.
 */
if ($uri === '/channels.php') {
    require __DIR__ . '/api/channels.php';
    return true;
}

/**
 * 4) Fallback:
 * Qualquer outra rota cai na UI principal (SPA-like simples).
 * Se quiser um 404 para rotas inexistentes, você pode checar padrões aqui
 * antes de incluir o index.php e emitir http_response_code(404).
 */
require $docroot . '/index.php';
