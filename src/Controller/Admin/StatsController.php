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
        !$this->isGranted('ROLE_ASSOC') &&     // si un jour tu corriges
        !$this->isGranted('ROLE_ASSOС')        // ✅ EXACT (copié depuis ton affichage)
    ) {
        throw $this->createAccessDeniedException();
    }


        $caseId = $request->query->get('case');
        $caseId = ($caseId !== null && $caseId !== '') ? (int) $caseId : null;

        $stats = $donationRepository->statsByCase($caseId);

        // dropdown list
        $cases = $caseSocialRepository->findBy([], ['createdAt' => 'DESC']);

        // totals (global)
        $grandTotal = 0.0;
        $grandCount = 0;
        foreach ($stats as $row) {
            $grandTotal += (float) $row['totalAmount'];
            $grandCount += (int) $row['donationsCount'];
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
