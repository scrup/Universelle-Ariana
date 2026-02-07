<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Cha9a9aFundService
{
    public function __construct(private HttpClientInterface $http) {}

    public function fetchStatsByName(string $campaignName, int $maxPages = 15): array
    {
        $detailUrl = $this->findDetailUrlByName($campaignName, $maxPages);

        if (!$detailUrl) {
            return [
                'error' => 'Not found on Cha9a9a list',
                'campaignName' => $campaignName,
            ];
        }

        return $this->fetchStatsFromDetail($detailUrl);
    }

    public function fetchStatsFromDetail(string $detailUrl): array
    {
        $html = $this->fetchHtml($detailUrl);
        $crawler = new Crawler($html);

        $title = trim($crawler->filter('h2')->first()->text(''));
        $fullText = preg_replace('/\s+/', ' ', $crawler->text()) ?? '';

        // "20 000 DT collectés sur 20 000 DT"
        [$collected, $goal] = $this->parseCollectedGoal($fullText);

        // "332 Participant(s)"
        $participants = $this->parseParticipants($fullText);

        // "0 Jour(s) restant(s)"
        $daysRemaining = $this->parseDaysRemaining($fullText);

        $score = ($goal > 0) ? ($collected / $goal) : 0.0;
        $percent = (int) round($score * 100);


        return [
            'title' => $title,
            'detailUrl' => $detailUrl,
            'collected' => $collected,
            'goal' => $goal,
            'score' => $score,       // 0..1
            'percent' => $percent,   // 0..100
            'participants' => $participants,
            'daysRemaining' => $daysRemaining,
        ];
    }

    private function findDetailUrlByName(string $campaignName, int $maxPages): ?string
    {
        $needle = $this->normalize($campaignName);

        for ($page = 1; $page <= $maxPages; $page++) {
            $listUrl = $page === 1
                ? 'https://www.cha9a9a.tn/fund/list'
                : 'https://www.cha9a9a.tn/fund/list?page=' . $page;

            $html = $this->fetchHtml($listUrl);
            $crawler = new Crawler($html);

            $found = null;

            // Look for links pointing to detail pages
            $crawler->filter('a[href*="/fund/detail/"]')->each(function (Crawler $a) use (&$found, $needle) {
                if ($found) return;

                $href = $a->attr('href') ?? '';
                $text = $this->normalize($a->text(''));

                // resilient: contains match
                if ($text !== '' && str_contains($text, $needle)) {
                    $found = $this->absoluteUrl($href);
                }
            });

            if ($found) return $found;
        }

        return null;
    }

    private function fetchHtml(string $url): string
    {
        return $this->http->request('GET', $url, [
            'headers' => [
                'User-Agent' => 'UniverselleCelluleBot/1.0',
                'Accept-Language' => 'fr,en;q=0.8',
            ],
            'timeout' => 20,
        ])->getContent();
    }

    private function absoluteUrl(string $href): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }
        if (!str_starts_with($href, '/')) {
            $href = '/' . $href;
        }
        return 'https://www.cha9a9a.tn' . $href;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/\s+/', ' ', $s);
        return $s ?? '';
    }

private function parseCollectedGoal(string $text): array
{
    // normalize weird spaces: NBSP (U+00A0) and NNBSP (U+202F)
    $text = str_replace(["\u{00A0}", "\u{202F}"], ' ', $text);

    // Accept: collectés / collecté / Collecté etc.
    if (preg_match('/([0-9][0-9\s]*)\s*DT\s*collect[ée]s?\s*sur\s*([0-9][0-9\s]*)\s*DT/iu', $text, $m)) {
        // SUPER robust: keep only digits (removes spaces + any weird separators)
        $collected = (int) preg_replace('/[^\d]/u', '', $m[1]);
        $goal      = (int) preg_replace('/[^\d]/u', '', $m[2]);
        return [$collected, $goal];
    }

    return [0, 0];
}


    private function parseParticipants(string $text): ?int
    {
        if (preg_match('/(\d+)\s*Participant\(s\)/i', $text, $m)) return (int)$m[1];
        if (preg_match('/(\d+)\s*Participant\(S\)/i', $text, $m)) return (int)$m[1];
        return null;
    }

    private function parseDaysRemaining(string $text): ?int
    {
        if (preg_match('/(\d+)\s*Jour\(s\)\s*restant\(s\)/i', $text, $m)) return (int)$m[1];
        if (preg_match('/(\d+)\s*Jour\(S\)\s*Restant\(S\)/i', $text, $m)) return (int)$m[1];
        return null;
    }
}
