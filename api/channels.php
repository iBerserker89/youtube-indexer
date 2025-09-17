<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\{Validate, CacheFs, ChannelService, YoutubeClient};

$topic     = Validate::topic($_GET['topic'] ?? '');
$lang      = Validate::lang($_GET['lang'] ?? null);
$country   = Validate::country($_GET['country'] ?? null);
$pageToken = Validate::pageToken($_GET['pageToken'] ?? null);

$cache = new CacheFs(dirname(__DIR__) . '/storage/cache');
$key   = "channels|$topic|$lang|$country|" . ($pageToken ?? '-');
if ($hit = $cache->get($key, 6*60*60)) { echo json_encode($hit); exit; }

$service = new ChannelService(new YoutubeClient());
$payload = $service->fetch($topic, $lang, $country, $pageToken);

$cache->set($key, $payload);
echo json_encode($payload);
