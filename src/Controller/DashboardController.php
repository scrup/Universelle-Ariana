<?php

namespace App\Controller;

use App\Repository\CaseSocialRepository;
use App\Repository\DonationRepository;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        CaseSocialRepository $caseRepo,
        DonationRepository $donationRepo,
        EvenementRepository $eventRepo
    ): Response {
        return $this->render('dashboard/index.html.twig', [
            'total_cases' => $caseRepo->count([]),
            'total_donations' => $donationRepo->count([]),
            'total_events' => $eventRepo->count([]),
            'recent_cases' => $caseRepo->findBy([], ['createdAt' => 'DESC'], 5),
            'recent_donations' => $donationRepo->findBy([], ['donatedAt' => 'DESC'], 5),
        ]);
    }
}