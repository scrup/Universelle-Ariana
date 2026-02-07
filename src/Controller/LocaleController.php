<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    #[Route('/_locale/{locale}', name: 'app_set_locale')]
    public function setLocale(string $locale, Request $request): Response
    {
        $request->getSession()->set('_locale', $locale);
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?? $this->generateUrl('app_homepage'));
    }
}
