<?php
namespace App\Controller;

use App\Entity\CaseSocial;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AssociationDashboardController extends AbstractController
{
    #[Route('/association/dashboard', name: 'app_association_dashboard')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ASSOC');
        $user = $this->getUser();

        $repo = $doctrine->getRepository(CaseSocial::class);
        
        // Get cases published by this association
        $myCases = $repo->findBy(['publisher' => $user], ['createdAt' => 'DESC']);
        
        // Count by status
        $pendingCases = array_filter($myCases, fn($c) => $c->getStatus() === CaseSocial::STATUS_PENDING);
        $publishedCases = array_filter($myCases, fn($c) => $c->getStatus() === CaseSocial::STATUS_PUBLISHED);
        $rejectedCases = array_filter($myCases, fn($c) => $c->getStatus() === CaseSocial::STATUS_REJECTED);

        // Total views and donations
        $totalViews = array_sum(array_map(fn($c) => $c->getViewsCount(), $myCases));
        $totalDonations = array_sum(array_map(fn($c) => count($c->getDonations()), $myCases));

        return $this->render('association/dashboard.html.twig', [
            'myCases' => $myCases,
            'pendingCount' => count($pendingCases),
            'publishedCount' => count($publishedCases),
            'rejectedCount' => count($rejectedCases),
            'totalViews' => $totalViews,
            'totalDonations' => $totalDonations,
        ]);
    }
}
