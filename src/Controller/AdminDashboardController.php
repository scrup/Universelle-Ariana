<?php
namespace App\Controller;

use App\Entity\CaseSocial;
use App\Entity\Donation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

final class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();

        $caseRepo = $em->getRepository(CaseSocial::class);
        $donationRepo = $em->getRepository(Donation::class);

        $totalCases = (int) $caseRepo->createQueryBuilder('c')
            ->select('count(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $urgentCases = (int) $caseRepo->createQueryBuilder('c')
            ->select('count(c.id)')
            ->where('c.isUrgent = true')
            ->getQuery()
            ->getSingleScalarResult();

        $totalDonations = (float) $donationRepo->createQueryBuilder('d')
            ->select('coalesce(sum(d.amount),0)')
            ->getQuery()
            ->getSingleScalarResult();

        // pending cases list
        $pendingCases = $caseRepo->findBy(['status' => CaseSocial::STATUS_PENDING], ['createdAt' => 'DESC']);

        return $this->render('admin/dashboard.html.twig', [
            'totalCases' => $totalCases,
            'urgentCases' => $urgentCases,
            'totalDonations' => $totalDonations,
            'pendingCases' => $pendingCases,
        ]);
    }

    #[Route('/admin/case/{id}/approve', name: 'app_admin_case_approve', methods: ['POST'])]
    public function approve(CaseSocial $case, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Admin access required');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('approve' . $case->getId(), $token)) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        $em = $doctrine->getManager();
        $case->setStatus(CaseSocial::STATUS_PUBLISHED);
        $em->flush();

        $this->addFlash('success', 'Case approved successfully.');

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/admin/case/{id}/reject', name: 'app_admin_case_reject', methods: ['POST'])]
    public function reject(CaseSocial $case, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Admin access required');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reject' . $case->getId(), $token)) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        $em = $doctrine->getManager();
        $case->setStatus(CaseSocial::STATUS_REJECTED);
        $em->flush();

        $this->addFlash('success', 'Case rejected.');

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
