<?php
namespace App;

/**
 * Utilitário de configuração.
 * Responsável por expor valores de configuração do app.
 * Atualmente fornece apenas a leitura da chave da YouTube Data API v3.
 */
final class Config
{
    /**
     * Obtém a chave da YouTube Data API v3 (uso server-side).
     *
     * Ordem de resolução:
     * 1) Variável de ambiente `YT_API_KEY` (preferível em produção).
     * 2) Fallback: lê `config.php` na raiz e usa a constante `YT_API_KEY`, se definida.
     *
     * Retorna string vazia caso a chave não esteja disponível.
     *
     * @return string Chave da API ou '' se não encontrada.
     */
    public static function ytApiKey(): string
    {
        $env = getenv('YT_API_KEY');
        if ($env) return $env;

        $cfg = dirname(__DIR__) . '/config.php';
        if (is_file($cfg)) {
            include_once $cfg;
            if (defined('YT_API_KEY')) return YT_API_KEY;
        }
        return '';
    }
}

