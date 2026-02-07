<?php

namespace App\Controller;

use App\Entity\CasePhoto;
use App\Entity\CaseSocial;
use App\Form\CaseSocialType;
use App\Repository\CaseSocialRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CategorieRepository;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/case/social')]
final class CaseSocialController extends AbstractController
{
  #[Route(name: 'app_case_social_index', methods: ['GET'])]
public function index(
    Request $request,
    CaseSocialRepository $caseSocialRepository,
    CategorieRepository $categorieRepository
): Response
{
    $q = $request->query->get('q', '');
    $category = $request->query->get('category');
    $sort = $request->query->get('sort', '');

    $categoryId = $category !== null && $category !== '' ? (int) $category : null;

    $all = $caseSocialRepository->searchFilterSort($q, $categoryId, $sort);

    return $this->render('case_social/index.html.twig', [
        'case_socials' => $all,
        'caseSocials' => $all,
        'cases' => $all,

        // for the category dropdown in twig
        'categories' => $categorieRepository->findAll(),
    ]);
}
    #[Route('/new', name: 'app_case_social_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $caseSocial = new CaseSocial();
        $form = $this->createForm(CaseSocialType::class, $caseSocial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // assign the currently logged-in association as publisher
            $user = $this->getUser();
            if ($user) {
                $caseSocial->setPublisher($user);
            }

            $caseSocial->setStatus(CaseSocial::STATUS_PUBLISHED);
            $entityManager->persist($caseSocial);
            $entityManager->flush();

            // handle uploaded images (unmapped 'images' field)
            $uploadedFiles = $form->get('images')->getData();
            if ($uploadedFiles) {
                $caseId = $caseSocial->getId();
                $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cases/' . $caseId;
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                foreach ($uploadedFiles as $uploadedFile) {
                    if (!$uploadedFile instanceof UploadedFile) {
                        continue;
                    }

                    $original = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safe = preg_replace('/[^a-z0-9-_]+/i', '-', $original);
                    $newFilename = $safe . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                    try {
                        $uploadedFile->move($targetDir, $newFilename);
                    } catch (FileException $e) {
                        continue;
                    }

                    $photo = new CasePhoto();
                    $photo->setFilePath('uploads/cases/' . $caseId . '/' . $newFilename);
                    $photo->setCaseSocial($caseSocial);
                    $entityManager->persist($photo);
                }

                $entityManager->flush();
            }

            return $this->redirectToRoute('app_case_social_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('case_social/new.html.twig', [
            'case_social' => $caseSocial,
            'caseSocial' => $caseSocial,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_case_social_show', methods: ['GET'])]
    public function show(CaseSocial $caseSocial, EntityManagerInterface $entityManager): Response
    {
        // increment views count (persist immediately)
        $caseSocial->incrementViews();
        $entityManager->flush();

        return $this->render('case_social/show.html.twig', [
            'case_social' => $caseSocial,
            'caseSocial' => $caseSocial,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_case_social_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CaseSocial $caseSocial, EntityManagerInterface $entityManager): Response
    {
        // only allow publisher or admin to edit
        if (!$this->isGranted('ROLE_ADMIN') && $caseSocial->getPublisher() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CaseSocialType::class, $caseSocial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // handle uploaded images on edit
            $uploadedFiles = $form->get('images')->getData();
            if ($uploadedFiles) {
                $caseId = $caseSocial->getId();
                $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cases/' . $caseId;
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                foreach ($uploadedFiles as $uploadedFile) {
                    if (!$uploadedFile instanceof UploadedFile) {
                        continue;
                    }

                    $original = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safe = preg_replace('/[^a-z0-9-_]+/i', '-', $original);
                    $newFilename = $safe . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                    try {
                        $uploadedFile->move($targetDir, $newFilename);
                    } catch (FileException $e) {
                        continue;
                    }

                    $photo = new CasePhoto();
                    $photo->setFilePath('uploads/cases/' . $caseId . '/' . $newFilename);
                    $photo->setCaseSocial($caseSocial);
                    $entityManager->persist($photo);
                }

                $entityManager->flush();
            }

            return $this->redirectToRoute('app_case_social_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('case_social/edit.html.twig', [
            'case_social' => $caseSocial,
            'caseSocial' => $caseSocial,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_case_social_delete', methods: ['POST'])]
    public function delete(Request $request, CaseSocial $caseSocial, EntityManagerInterface $entityManager): Response
    {
        // only allow publisher or admin to delete
        if (!$this->isGranted('ROLE_ADMIN') && $caseSocial->getPublisher() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$caseSocial->getId(), $token)) {
            $entityManager->remove($caseSocial);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_case_social_index', [], Response::HTTP_SEE_OTHER);
    }
}
