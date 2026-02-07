<?php
namespace App\Controller;

use App\Entity\CaseSocial;
use App\Entity\Donation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class DonationController extends AbstractController
{
    #[Route('/case/social/{id}/donate', name: 'app_case_social_donate', methods: ['GET','POST'])]
    public function donate(CaseSocial $case, Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DONATEUR');

        $donation = new Donation();

        $form = $this->createFormBuilder($donation)
            ->add('amount', MoneyType::class, ['currency' => 'MAD', 'scale' => 3])
            ->add('note', TextareaType::class, ['required' => false])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $donation = $form->getData();
            $donation->setCaseSocial($case);
            $donation->setDonor($this->getUser());
            $donation->setStatus(Donation::STATUS_DECLARED);

            $em = $doctrine->getManager();
            $em->persist($donation);
            $em->flush();

            $this->addFlash('success', 'Thank you for your donation (declared).');
            return $this->redirectToRoute('app_case_social_show', ['id' => $case->getId()]);
        }

        return $this->render('donation/donate.html.twig', [
            'form' => $form->createView(),
            'case' => $case,
            'caseSocial' => $case,
        ]);
    }

    #[Route('/profile/donations', name: 'app_profile_donations')]
    public function myDonations(ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        $repo = $doctrine->getRepository(Donation::class);
        $donations = $repo->findBy(['donor' => $user], ['donatedAt' => 'DESC']);

        return $this->render('profile/donations.html.twig', [
            'donations' => $donations,
        ]);
    }
}
