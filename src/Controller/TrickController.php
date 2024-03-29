<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\CommentType;
use App\Form\TrickType;
use App\Repository\CommentRepository;
use App\Repository\TrickRepository;
use App\Service\UploadFile;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/trick')]
class TrickController extends AbstractController {

    #[Route('/', name: 'app_trick_index', methods: ['GET'])]
    public function index(TrickRepository $trickRepository): Response {

        return $this->render('trick/index.html.twig', [
            'tricks' => $trickRepository->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_trick_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UploadFile $uploadFile, TrickRepository $trickRepository): Response {

        $trick = new Trick();
        $form = $this->createForm(TrickType::class, $trick, ['validation_groups' => 'trick_new']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($form->getData()->getName());
            $trick->setSlug(strtolower($slug));
            $trick->setUser($this->getUser());

            $uploadFile->uploadImage($trick);
            $uploadFile->uploadVideo($trick);
            $trickRepository->add($trick, true);

            $this->addFlash("success", "Votre figure a bien été créée.");
            return $this->redirectToRoute('app_trick_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('trick/new.html.twig', [
            'trick' => $trick,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'app_trick_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Trick $trick, CommentRepository $commentRepository): Response {

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        //paginate comments
        $offset = max(0, $request->query->getInt("offset"));
        $comments = $commentRepository->findCommentPaginated($offset, $trick);
        $nbPages = (ceil(count($comments) / 10)) - 1;

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUser($this->getUser());
            $comment->setTrick($trick);
            $commentRepository->add($comment, true);

            return $this->redirectToRoute('app_trick_show', ['slug' => $trick->getSlug()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('trick/show.html.twig', [
            'offset' => $offset,
            'comments' => $comments,
            'trick' => $trick,
            'previous' => $offset - 10,
            'next' => min(count($comments), $offset + 10),
            'nbPages' => $nbPages,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_trick_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UploadFile $uploadFile, Trick $trick, TrickRepository $trickRepository): Response {

        $this->denyAccessUnlessGranted('POST_VIEW', $this->getUser());
        $form = $this->createForm(TrickType::class, $trick, ['validation_groups' => 'trick_edit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($form->getData()->getName());
            $trick->setSlug(strtolower($slug));
            $trick->setUpdatedAt(date_create_immutable());

            $uploadFile->uploadImage($trick);
            $uploadFile->uploadVideo($trick);
            $trickRepository->add($trick, true);

            $this->addFlash("success", "Votre figure a bien été modifiée.");
            return $this->redirectToRoute('app_trick_show', ['slug' => $trick->getSlug()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('trick/edit.html.twig', [
            'trick' => $trick,
            'form' => $form,
        ]);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('/delete/{slug}', name: 'app_trick_delete', methods: ['POST'])]
    public function delete(Request $request, Trick $trick, TrickRepository $trickRepository): Response {

        if ($this->isCsrfTokenValid(sprintf('delete%s', $trick->getId()), $request->request->get('_token'))) {
            $trickRepository->remove($trick, true);
        }

        $this->addFlash("success", "Votre figure a bien été supprimée.");
        return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
    }
}
