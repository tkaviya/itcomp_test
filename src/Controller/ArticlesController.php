<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticlesController extends AbstractController
{
    /**
     * @Route("/articles", name="app_articles")
     * @param ArticleRepository $articleRepository
     * @param Request $request
     * @return Response
     */
    public function index(ArticleRepository $articleRepository, Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $articles = $articleRepository->findAllByDateQueryBuilder();
        $pagerFanta = new Pagerfanta(new QueryAdapter($articles));
        $pagerFanta->setMaxPerPage(10);
        $pagerFanta->setCurrentPage($request->query->get('page', 1));

        return $this->render('articles/index.html.twig', [
            'pager' => $pagerFanta
        ]);
    }
}
