<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HowController extends AbstractController
{
    #[Route('/how/discover', name: 'app_how_discover')]
    public function discover(): Response
    {
        return $this->render('pages/how/discover.html.twig');
    }

    #[Route('/how/donate', name: 'app_how_donate')]
    public function donate(): Response
    {
        return $this->render('pages/how/donate.html.twig');
    }

    #[Route('/how/impact', name: 'app_how_impact')]
    public function impact(): Response
    {
        return $this->render('pages/how/impact.html.twig');
    }
}
