<?php
namespace App;

final class YoutubeClient
{
    private string $key;
    private const BASE = 'https://www.googleapis.com/youtube/v3';

    public function __construct(?string $key = null) {
        $this->key = $key ?? Config::ytApiKey();
        if ($this->key === '') {
            throw new \RuntimeException('YT_API_KEY not configured.');
        }
    }

    /**
     * Faz uma requisição GET à API do YouTube.
     *
     * @param array<string, string|int|null> $query  Query string (será mesclada com a API key)
     * @return array<string,mixed>                   Decodificado como array associativo
     */
    private function get(string $path, array $query): array {
        $query['key'] = $this->key;

        $url = self::BASE . $path . '?' . http_build_query($query);
        $ch  = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        /** @var string|false $res */
        $res  = curl_exec($ch);
        /** @var int $code */
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        // Erro na requisição
        if (!is_string($res) || $code < 200 || $code >= 300) {
            throw new \RuntimeException("YouTube API error: HTTP $code - $err");
        }

        $data = json_decode($res, true);

        // Erro ao decodificar JSON
        if (!is_array($data)) {
            $data = [];
        }

        /** @var array<string,mixed> $data */
        return $data;
    }

    /**
     * Chamada ao endpoint /search.
     *
     * @return array<string,mixed>
     */
    public function search(string $topic, string $lang, string $country, ?string $pageToken): array {
        return $this->get('/search', array_filter([
            'part'              => 'snippet',
            'type'              => 'video',
            'q'                 => $topic,
            'relevanceLanguage' => $lang,
            'regionCode'        => $country,
            'maxResults'        => 25,
            'pageToken'         => $pageToken,
        ], static fn($v) => $v !== null && $v !== ''));
    }

    /**
     * Chamada ao endpoint /channels.
     *
     * @param list<string> $ids
     * @return array<string,mixed>
     */
    public function channels(array $ids): array {
        if ($ids === []) {
            return ['items' => []];
        }

        return $this->get('/channels', [
            'part'       => 'snippet,statistics,topicDetails',
            'id'         => implode(',', $ids),
            'maxResults' => 50,
        ]);
    }
}
