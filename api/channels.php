<?php
declare(strict_types=1);

use App\Config;
use App\CacheFs;

header('Content-Type: application/json; charset=utf-8');

// Autoload dos utilitários
require_once dirname(__DIR__) . '/lib/Config.php';
require_once dirname(__DIR__) . '/lib/CacheFs.php';

// Lê a API key
$apiKey = Config::ytApiKey();
if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Missing API key. Set env YT_API_KEY or create config.php with define("YT_API_KEY", "...").'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Inputs
$topic   = isset($_GET['topic'])   ? trim((string)$_GET['topic'])   : '';
$lang    = isset($_GET['lang'])    ? trim((string)$_GET['lang'])    : '';
$country = isset($_GET['country']) ? trim((string)$_GET['country']) : '';
$pageTok = isset($_GET['pageToken']) ? trim((string)$_GET['pageToken']) : '';

if ($topic === '') {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Missing required parameter: topic'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ----- Cache (Filesystem) -----
$cache = new CacheFs(dirname(__DIR__) . '/storage/cache');
$cacheTtl = 6 * 60 * 60; // 6 horas

$cacheKey = 'channels?' . http_build_query([
    'q' => $topic,
    'lang' => $lang,
    'country' => $country,
    'pageToken' => $pageTok
], '', '&', PHP_QUERY_RFC3986);

if ($cached = $cache->get($cacheKey, $cacheTtl)) {
    echo json_encode($cached, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Executa uma chamada HTTP GET e retorna o JSON decodificado como array.
 *
 * - Em caso de erro de transporte cURL, retorna ['__http_error' => <mensagem>].
 * - Em caso de resposta não-JSON, retorna ['__parse_error' => 'Invalid JSON from YouTube'].
 * - Sempre anexa '__status' com o código HTTP quando disponível.
 *
 * @param string                $url      URL completa do endpoint.
 * @param array<int,string>     $headers  Cabeçalhos HTTP no formato "Nome: valor".
 * @param int                   $timeout  Timeout em segundos (padrão 12).
 *
 * @return array<string,mixed>  Array resultante do JSON + metadados ('__status', ...).
 */
function http_get_json(string $url, array $headers = [], int $timeout = 12): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => $headers
    ]);
    $res = curl_exec($ch);
    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['__http_error' => $err];
    }
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $json = json_decode($res, true);
    if (!is_array($json)) $json = ['__parse_error' => 'Invalid JSON from YouTube'];
    $json['__status'] = $status;
    return $json;
}

/**
 * Monta a URL completa para a YouTube Data API v3 com endpoint e parâmetros.
 * Garante que a API key seja incluída via query string.
 *
 * @param string                $endpoint  Ex.: 'search', 'channels'.
 * @param array<string,mixed>   $params    Parâmetros de query (part, q, type, etc.).
 * @param string                $apiKey    Chave de API (server-side).
 *
 * @return string               URL final pronta para requisição.
 */
function yt_build_url(string $endpoint, array $params, string $apiKey): string {
    $base = 'https://www.googleapis.com/youtube/v3/' . ltrim($endpoint, '/');
    $params['key'] = $apiKey;
    return $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

// ----- Buscar vídeos (region-aware) e deduplicar canais -----
$searchParams = [
    'part' => 'snippet',
    'type' => 'video',
    'maxResults' => 25,
    'q' => $topic,
];
if ($lang !== '')    $searchParams['relevanceLanguage'] = $lang;
if ($country !== '') $searchParams['regionCode'] = $country;
if ($pageTok !== '') $searchParams['pageToken'] = $pageTok;

$searchUrl  = yt_build_url('search', $searchParams, $apiKey);
$searchJson = http_get_json($searchUrl);

if (isset($searchJson['error'])) {
    http_response_code(502);
    echo json_encode(['error' => true, 'message' => $searchJson['error']['message'] ?? 'YouTube API error'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if (($searchJson['__status'] ?? 0) >= 400) {
    http_response_code(502);
    echo json_encode(['error' => true, 'message' => 'YouTube API HTTP ' . $searchJson['__status']],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$items = $searchJson['items'] ?? [];
$channelIds = [];
foreach ($items as $it) {
    if (isset($it['snippet']['channelId'])) {
        $channelIds[] = $it['snippet']['channelId'];
    }
}
$channelIds = array_values(array_unique($channelIds));

if (count($channelIds) === 0) {
    $response = [
        'topic' => $topic,
        'lang' => $lang,
        'country' => $country,
        'pageInfo' => [
            'resultsPerPage' => $searchJson['pageInfo']['resultsPerPage'] ?? 0,
            'totalResults' => 0,
        ],
        'nextPageToken' => $searchJson['nextPageToken'] ?? null,
        'channels' => []
    ];
    $cache->set($cacheKey, $response);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ----- Enriquecer canais -----
$channelsParams = [
    'part' => 'snippet,statistics,topicDetails',
    'id' => implode(',', array_slice($channelIds, 0, 50))
];
$channelsUrl  = yt_build_url('channels', $channelsParams, $apiKey);
$channelsJson = http_get_json($channelsUrl);

if (isset($channelsJson['error'])) {
    http_response_code(502);
    echo json_encode(['error' => true, 'message' => $channelsJson['error']['message'] ?? 'YouTube API error'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$channels = [];
foreach (($channelsJson['items'] ?? []) as $ch) {
    $id = $ch['id'] ?? null;
    if (!$id) continue;
    $snippet = $ch['snippet'] ?? [];
    $stats   = $ch['statistics'] ?? [];
    $topics  = $ch['topicDetails']['topicCategories'] ?? [];

    $channels[] = [
        'id' => $id,
        'title' => $snippet['title'] ?? '',
        'description' => $snippet['description'] ?? '',
        'publishedAt' => $snippet['publishedAt'] ?? null,
        'country' => $snippet['country'] ?? null,
        'customUrl' => $snippet['customUrl'] ?? null,
        'thumbnail' => $snippet['thumbnails']['default']['url'] ?? null,
        'statistics' => [
            'subscriberCount' => isset($stats['subscriberCount']) ? (int)$stats['subscriberCount'] : null,
            'videoCount' => isset($stats['videoCount']) ? (int)$stats['videoCount'] : null,
            'viewCount' => isset($stats['viewCount']) ? (int)$stats['viewCount'] : null,
        ],
        'topicCategories' => $topics,
        'channelUrl' => 'https://www.youtube.com/channel/' . $id,
    ];
}

// Ordena por inscritos (quando disponível)
usort($channels, function($a, $b) {
    $sa = $a['statistics']['subscriberCount'] ?? 0;
    $sb = $b['statistics']['subscriberCount'] ?? 0;
    return $sb <=> $sa;
});

$response = [
    'topic' => $topic,
    'lang' => $lang,
    'country' => $country,
    'nextPageToken' => $searchJson['nextPageToken'] ?? null,
    'channels' => $channels
];

// Salva no cache e responde
$cache->set($cacheKey, $response);

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
