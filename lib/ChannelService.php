<?php
namespace App;

/**
 * @phpstan-type SearchItem array{
 *   snippet?: array{ channelId?: string }
 * }
 * @phpstan-type SearchResult array{
 *   items?: list<SearchItem>,
 *   pageInfo?: array{ resultsPerPage?: int },
 *   nextPageToken?: string|null
 * }
 *
 * @phpstan-type ChannelSnippet array{
 *   title?: string,
 *   description?: string,
 *   publishedAt?: string,
 *   country?: string|null,
 *   customUrl?: string|null,
 *   thumbnails?: array{
 *     default?: array{ url?: string }
 *   }
 * }
 * @phpstan-type ChannelStatistics array{
 *   subscriberCount?: int|string,
 *   videoCount?: int|string,
 *   viewCount?: int|string
 * }
 * @phpstan-type ChannelItem array{
 *   id?: string,
 *   snippet?: ChannelSnippet,
 *   statistics?: ChannelStatistics,
 *   topicDetails?: array{ topicCategories?: list<string> }
 * }
 * @phpstan-type ChannelsResponse array{
 *   items?: list<ChannelItem>
 * }
 *
 * @phpstan-type MappedChannel array{
 *   id: string,
 *   title: string,
 *   description: string,
 *   publishedAt: string|null,
 *   country: string|null,
 *   customUrl: string|null,
 *   thumbnail: string|null,
 *   statistics: array{
 *     subscriberCount: int|null,
 *     videoCount: int|null,
 *     viewCount: int|null
 *   },
 *   topicCategories: list<string>,
 *   channelUrl: string
 * }
 */
final class ChannelService
{
    /** @var YoutubeClient */
    private YoutubeClient $yt;

    public function __construct(?YoutubeClient $yt = null)
    {
        $this->yt = $yt ?? new YoutubeClient();
    }

    /**
     * Fluxo completo: busca vídeos por tema/região, deduplica canais,
     * enriquece com /channels, mapeia e ordena por inscritos.
     *
     * @return array{
     *   topic:string,
     *   lang:string,
     *   country:string,
     *   nextPageToken:string|null,
     *   channels:list<MappedChannel>
     * }
     */
    public function fetch(string $topic, string $lang, string $country, ?string $pageToken): array
    {
        /** @var SearchResult $search */
        $search = $this->yt->search($topic, $lang, $country, $pageToken);

        // dedupe channelIds
        $ids = [];

        /** @var list<SearchItem> $items */
        $items = $search['items'] ?? [];
        foreach ($items as $it) {
            $snippet = $it['snippet'] ?? [];
            $cid = $snippet['channelId'] ?? null;
            if (is_string($cid) && $cid !== '' && !in_array($cid, $ids, true)) {
                $ids[] = $cid;
            }
        }

        if ($ids === []) {
            return [
                'topic'         => $topic,
                'lang'          => $lang,
                'country'       => $country,
                'nextPageToken' => $search['nextPageToken'] ?? null,
                'channels'      => [],
            ];
        }

        /** @var ChannelsResponse $details */
        $details  = $this->yt->channels(array_slice($ids, 0, 50));

        /** @var list<MappedChannel> $channels */
        $channels = $this->mapChannels($details);

        // sort by subscribers desc (trata int|string|null com segurança)
        $toInt = static function ($v): int {
            return is_int($v) ? $v : (is_string($v) && is_numeric($v) ? (int)$v : 0);
        };

        usort(
            $channels,
            /**
             * @param MappedChannel $a
             * @param MappedChannel $b
             */
            static fn(array $a, array $b) =>
                $toInt($b['statistics']['subscriberCount'])
                <=> $toInt($a['statistics']['subscriberCount'])
        );

        return [
            'topic'         => $topic,
            'lang'          => $lang,
            'country'       => $country,
            'nextPageToken' => $search['nextPageToken'] ?? null,
            'channels'      => $channels,
        ];
    }

    /**
     * Mapeia a resposta do /channels para o formato desejado.
     *
     * @param ChannelsResponse $details Resposta bruta do /channels
     * @return list<MappedChannel> Lista de canais mapeados
     */
    private function mapChannels(array $details): array
    {
        $out = [];

        /** @var list<ChannelItem> $items */
        $items = $details['items'] ?? [];

        foreach ($items as $ch) {
            // antes: isset($ch['id']) && is_string($ch['id']) ? $ch['id'] : null
            /** @var string|null $id */
            $id = $ch['id'] ?? null;
            if (!is_string($id) || $id === '') {
                continue;
            }

            $sn = $ch['snippet'] ?? [];
            $st = $ch['statistics'] ?? [];
            $tp = isset($ch['topicDetails']['topicCategories'])
                ? $ch['topicDetails']['topicCategories']
                : [];

            // thumbnail (se existir a chave, pelo shape é string)
            $thumbnail = null;
            if (isset($sn['thumbnails']['default']['url'])) {
                /** @var string $url */
                $url = $sn['thumbnails']['default']['url'];
                $thumbnail = $url;
            }

            $toNullableInt = static function ($v): ?int {
                if (is_int($v)) return $v;
                if (is_string($v) && is_numeric($v)) return (int)$v;
                return null;
            };

            $out[] = [
                'id'          => $id,
                'title'       => $sn['title']       ?? '',
                'description' => $sn['description'] ?? '',
                'publishedAt' => $sn['publishedAt'] ?? null,
                'country'     => $sn['country']     ?? null,
                'customUrl'   => $sn['customUrl']   ?? null,
                'thumbnail'   => $thumbnail,
                'statistics'  => [
                    'subscriberCount' => $toNullableInt($st['subscriberCount'] ?? null),
                    'videoCount'      => $toNullableInt($st['videoCount'] ?? null),
                    'viewCount'       => $toNullableInt($st['viewCount'] ?? null),
                ],
                /** @var list<string> $tp */
                'topicCategories' => $tp,
                'channelUrl'      => 'https://www.youtube.com/channel/' . $id,
            ];
        }

        /** @var list<MappedChannel> $out */
        return $out;
    }
}
