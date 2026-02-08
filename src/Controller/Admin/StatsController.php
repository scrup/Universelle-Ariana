<?php

namespace App\Controller\Admin;

use App\Repository\DonationRepository;
use App\Repository\CaseSocialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/stats')]
class StatsController extends AbstractController
{
    #[Route('/donations-by-case', name: 'admin_stats_donations_by_case', methods: ['GET'])]
    public function donationsByCase(
        Request $request,
        DonationRepository $donationRepository,
        CaseSocialRepository $caseSocialRepository
    ): Response {
        if (
            !$this->isGranted('ROLE_ADMIN') &&
            !$this->isGranted('ROLE_ASSOC') &&
            !$this->isGranted('ROLE_ASSOС')
        ) {
            throw $this->createAccessDeniedException();
        }

        $caseId = $request->query->get('case');
        $caseId = ($caseId !== null && $caseId !== '') ? (int) $caseId : null;

        $rawStats = $donationRepository->statsByCase($caseId);

        // dropdown list (all cases)
        $cases = $caseSocialRepository->findBy([], ['createdAt' => 'DESC']);

        // index stats by caseId (must exist in statsByCase result)
        $statsById = [];
        foreach ($rawStats as $r) {
            if (isset($r['caseId'])) {
                $statsById[(int) $r['caseId']] = $r;
            }
        }

        // build final stats: every case appears even if 0 donation
        $stats = [];
        foreach ($cases as $c) {
            // if filter is set, skip others
            if ($caseId && $c->getId() !== $caseId) {
                continue;
            }

            $id = $c->getId();
            $row = $statsById[$id] ?? [];

            $stats[] = [
                'caseId' => $id,
                'caseTitle' => $c->getTitle(),
                'cha9a9aLink' => method_exists($c, 'getCha9a9aLink') ? $c->getCha9a9aLink() : null,
                'totalAmount' => $row['totalAmount'] ?? 0,
                'donationsCount' => $row['donationsCount'] ?? 0,
            ];
        }

        // totals (global)
        $grandTotal = 0.0;
        $grandCount = 0;
        foreach ($stats as $row) {
            $grandTotal += (float) ($row['totalAmount'] ?? 0);
            $grandCount += (int) ($row['donationsCount'] ?? 0);
        }

        return $this->render('admin/stats/donations_by_case.html.twig', [
            'stats' => $stats,
            'cases' => $cases,
            'selectedCaseId' => $caseId,
            'grandTotal' => $grandTotal,
            'grandCount' => $grandCount,
        ]);
    }
}
