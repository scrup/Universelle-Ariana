<?php
namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/events')]
final class EvenementController extends AbstractController
{
    #[Route('', name: 'app_evenement_index', methods: ['GET'])]
    public function index(EvenementRepository $repo): Response
    {
        $events = $repo->createQueryBuilder('e')
            ->orderBy('e.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('evenement/index.html.twig', ['events' => $events]);
    }

    #[Route('/new', name: 'app_evenement_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_ASSOC')) {
            throw $this->createAccessDeniedException();
        }

        $event = new Evenement();
        $form = $this->createForm(EvenementType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setCreatedBy($this->getUser());
            $em->persist($event);
            $em->flush();

            // handle uploaded image
            $uploaded = $form->get('image')->getData();
            if ($uploaded instanceof UploadedFile) {
                $eventId = $event->getId();
                $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events/' . $eventId;
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $original = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = preg_replace('/[^a-z0-9-_]+/i', '-', $original);
                $newFilename = $safe . '-' . uniqid() . '.' . $uploaded->guessExtension();
                try {
                    $uploaded->move($targetDir, $newFilename);
                    $event->setImagePath('uploads/events/' . $eventId . '/' . $newFilename);
                    $em->flush();
                } catch (FileException $e) {
                    // ignore upload errors for now
                }
            }

            $this->addFlash('success', 'Event created.');
            return $this->redirectToRoute('app_evenement_index');
        }

        return $this->render('evenement/new.html.twig', ['form' => $form->createView(), 'event' => $event]);
    }

    #[Route('/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $event): Response
    {
        return $this->render('evenement/show.html.twig', ['event' => $event]);
    }

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Evenement $event, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $event->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EvenementType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $uploaded = $form->get('image')->getData();
            if ($uploaded instanceof UploadedFile) {
                $eventId = $event->getId();
                $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events/' . $eventId;
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $original = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = preg_replace('/[^a-z0-9-_]+/i', '-', $original);
                $newFilename = $safe . '-' . uniqid() . '.' . $uploaded->guessExtension();
                try {
                    $uploaded->move($targetDir, $newFilename);
                    $event->setImagePath('uploads/events/' . $eventId . '/' . $newFilename);
                    $em->flush();
                } catch (FileException $e) {
                    // ignore upload errors for now
                }
            }

            $this->addFlash('success', 'Event updated.');
            return $this->redirectToRoute('app_evenement_index');
        }

        return $this->render('evenement/edit.html.twig', ['form' => $form->createView(), 'event' => $event]);
    }

    #[Route('/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $event, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $event->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Event deleted.');
        }

        return $this->redirectToRoute('app_evenement_index');
    }
}
