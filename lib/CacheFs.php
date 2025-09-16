<?php
namespace App;

/**
 * Cache simples usando arquivos JSON.
 * - Chave vira um sha1 (nome de arquivo)
 * - TTL em segundos
 */
final class CacheFs
{
    private string $dir;

    /**
     * Constrói o cache baseado em um diretório no filesystem.
     * Garante que o diretório exista (cria com permissões 0775 se necessário).
     *
     * @param string $dir Caminho do diretório onde os arquivos de cache serão armazenados.
     */
    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, '/');
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    /**
     * Resolve o caminho absoluto do arquivo de cache para uma chave lógica.
     * A chave é transformada em SHA-1 e salva com extensão “.json”.
     *
     * @param string $key Chave lógica do item em cache.
     * @return string Caminho completo para o arquivo JSON correspondente.
     */
    private function path(string $key): string
    {
        return $this->dir . '/' . sha1($key) . '.json';
    }

    /**
     * Lê um valor do cache, respeitando o TTL.
     * Retorna null se o arquivo não existir, estiver expirado ou o conteúdo não for JSON válido.
     *
     * @param string $key        Chave lógica do item em cache.
     * @param int    $ttlSeconds Tempo de vida (em segundos) a partir do mtime do arquivo.
     * @return array<string,mixed>|null Dados decodificados do JSON ou null se ausente/expirado/inválido.
     */
    public function get(string $key, int $ttlSeconds): ?array
    {
        $file = $this->path($key);
        if (!is_file($file)) return null;
        if (filemtime($file) + $ttlSeconds < time()) return null;

        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    /**
     * Persiste um valor no cache como JSON (substitui se já existir).
     *
     * @param string              $key   Chave lógica do item em cache.
     * @param array<string,mixed> $value Dados a serem serializados em JSON e gravados no arquivo.
     * @return void
     */
    public function set(string $key, array $value): void
    {
        file_put_contents(
            $this->path($key),
            json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
