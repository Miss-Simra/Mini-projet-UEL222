<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategoryRepository;


#[Route('/article')]
class ArticleController extends AbstractController
{
    // j'ai changé un peu la logique pour que la barre de recherche s'affiche correctement et que la barre de filtre marche aussi comme prévu
    #[Route('/', name: 'article_index', methods: ['GET'])]
    public function index(Request $request, ArticleRepository $articleRepository, CategoryRepository $categoryRepository): Response
    {
        $search = $request->query->get('q', '');
        $selectedCategory = $request->query->get('category', '');

        $queryBuilder = $articleRepository->createQueryBuilder('a');

        if (!empty($search)) {
            $queryBuilder
                ->andWhere('a.title LIKE :search OR a.content LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if (!empty($selectedCategory)) {
            $queryBuilder
                ->andWhere('a.category = :category')
                ->setParameter('category', $selectedCategory);
        }

        $articles = $queryBuilder->getQuery()->getResult();
        $categories = $categoryRepository->findAll();

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'search' => $search,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    #[Route('/new', name: 'article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('article_index', [], Response::HTTP_SEE_OTHER);
    }
}