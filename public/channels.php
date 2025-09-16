<?php
/**
 * Shim público para expor o endpoint de API em ambientes sem rewrite (.htaccess).
 *
 * Contexto:
 * - Servidor embutido do PHP e Render ignoram .htaccess; logo, /channels precisa existir no docroot.
 * - Este arquivo apenas inclui o endpoint real localizado fora do docroot (../api/channels.php),
 *   mantendo a lógica e a chave da API no servidor.
 *
 * Uso:
 * - Local (sem router): acesse /channels.php?topic=...&lang=...&country=...
 * - Com router.php: opcionalmente mapeie /channels -> este arquivo.
 * - Apache/cPanel: você pode mapear /channels -> channels.php via public/.htaccess.
 *
 * Observações de segurança:
 * - Não contém lógica nem segredos; todo o tratamento/headers é feito em api/channels.php.
 * - O include relativo pressupõe que a estrutura de diretórios (public/ e api/) seja mantida.
 */

// Public shim so PHP built-in server (and Render) can access the API without .htaccess rewrites.
require __DIR__ . '/../api/channels.php';
