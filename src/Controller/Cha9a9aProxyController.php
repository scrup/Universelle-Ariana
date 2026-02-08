<?php

namespace App\Controller;

use App\Service\Cha9a9aFundService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class Cha9a9aProxyController extends AbstractController
{
    #[Route('/api/cha9a9a/stats', name: 'api_cha9a9a_stats', methods: ['GET'])]
    public function stats(
        Cha9a9aFundService $cha9a9a,
        CacheInterface $cache,
        \Symfony\Component\HttpFoundation\Request $request
    ): JsonResponse {
        $name = trim((string) $request->query->get('name', ''));
        if ($name === '') {
            return $this->json(['error' => 'Missing name parameter'], 400);
        }

        $data = $cache->get('cha9a9a_stats_' . md5($name), function (ItemInterface $item) use ($cha9a9a, $name) {
            $item->expiresAfter(900); // 15 minutes
            return $cha9a9a->fetchStatsByName($name, 15);
        });

        return $this->json($data);
    }
    #[Route('/api/cha9a9a/stats-by-url', name: 'api_cha9a9a_stats_by_url', methods: ['GET'])]
public function statsByUrl(
    \Symfony\Component\HttpFoundation\Request $request,
    \App\Service\Cha9a9aFundService $cha9a9a,
    \Symfony\Contracts\Cache\CacheInterface $cache
): \Symfony\Component\HttpFoundation\JsonResponse {
    $url = trim((string) $request->query->get('url', ''));
    if ($url === '') {
        return $this->json(['error' => 'Missing url parameter'], 400);
    }

    $data = $cache->get('cha9a9a_url_' . md5($url), function (\Symfony\Contracts\Cache\ItemInterface $item) use ($cha9a9a, $url) {
        $item->expiresAfter(900);
        return $cha9a9a->fetchStatsFromDetail($url);
    });

    return $this->json($data);
}

}
