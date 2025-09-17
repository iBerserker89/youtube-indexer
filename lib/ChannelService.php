<?php
namespace App;

final class ChannelService
{
    public function __construct(private ?YoutubeClient $yt = null)
    {
        $this->yt = $yt ?? new YoutubeClient();
    }

    /**
     * Fluxo completo: busca vídeos por tema/região, deduplica canais,
     * enriquece com /channels, mapeia e ordena por inscritos.
     *
     * @return array<string,mixed> Payload pronto para JSON
     */
    public function fetch(string $topic, string $lang, string $country, ?string $pageToken): array
    {
        // search
        $search = $this->yt->search($topic, $lang, $country, $pageToken);

        // dedupe channelIds
        $ids = [];
        foreach ($search['items'] ?? [] as $it) {
            $cid = $it['snippet']['channelId'] ?? null;
            if ($cid && !in_array($cid, $ids, true)) $ids[] = $cid;
        }

        if ($ids === []) {
            return [
                'topic'        => $topic,
                'lang'         => $lang,
                'country'      => $country,
                'pageInfo'     => [
                    'resultsPerPage' => $search['pageInfo']['resultsPerPage'] ?? 0,
                    'totalResults'   => 0,
                ],
                'nextPageToken'=> $search['nextPageToken'] ?? null,
                'channels'     => []
            ];
        }

        // enrich
        $details  = $this->yt->channels(array_slice($ids, 0, 50));
        $channels = $this->mapChannels($details);

        // sort by subscribers desc
        usort($channels, fn($a,$b) =>
            (int)($b['statistics']['subscriberCount'] ?? 0)
            <=>
            (int)($a['statistics']['subscriberCount'] ?? 0)
        );

        return [
            'topic'        => $topic,
            'lang'         => $lang,
            'country'      => $country,
            'nextPageToken'=> $search['nextPageToken'] ?? null,
            'channels'     => $channels
        ];
    }
    
    /**
     * Mapeia a resposta do /channels para o formato desejado.
     *
     * @param array<string,mixed> $details Resposta bruta do /channels
     * @return array<int,array<string,mixed>> Lista de canais mapeados
     */
    private function mapChannels(array $details): array
    {
        $out = [];
        foreach (($details['items'] ?? []) as $ch) {
            $id = $ch['id'] ?? null; if (!$id) continue;
            $sn = $ch['snippet'] ?? [];
            $st = $ch['statistics'] ?? [];
            $tp = $ch['topicDetails']['topicCategories'] ?? [];

            $out[] = [
                'id'          => $id,
                'title'       => $sn['title'] ?? '',
                'description' => $sn['description'] ?? '',
                'publishedAt' => $sn['publishedAt'] ?? null,
                'country'     => $sn['country'] ?? null,
                'customUrl'   => $sn['customUrl'] ?? null,
                'thumbnail'   => $sn['thumbnails']['default']['url'] ?? null,
                'statistics'  => [
                    'subscriberCount' => isset($st['subscriberCount']) ? (int)$st['subscriberCount'] : null,
                    'videoCount'      => isset($st['videoCount']) ? (int)$st['videoCount'] : null,
                    'viewCount'       => isset($st['viewCount']) ? (int)$st['viewCount'] : null,
                ],
                'topicCategories' => $tp,
                'channelUrl'      => 'https://www.youtube.com/channel/' . $id,
            ];
        }
        return $out;
        }
}
