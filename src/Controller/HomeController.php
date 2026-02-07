<?php
namespace App\Controller;

use App\Entity\CaseSocial;
use App\Entity\Evenement;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $repo = $doctrine->getRepository(CaseSocial::class);

        // get published cases, urgent first then newest
        $cases = $repo->findBy(['status' => CaseSocial::STATUS_PUBLISHED], ['isUrgent' => 'DESC', 'createdAt' => 'DESC']);

        // upcoming events (next 3)
        $eventRepo = $doctrine->getRepository(Evenement::class);
        $qb = $eventRepo->createQueryBuilder('e')
            ->where('e.startAt >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startAt', 'ASC')
            ->setMaxResults(3);
        $events = $qb->getQuery()->getResult();

        return $this->render('home/index.html.twig', [
            'cases' => $cases,
            'events' => $events,
        ]);
    }
}
