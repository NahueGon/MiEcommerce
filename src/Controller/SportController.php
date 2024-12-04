<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\SportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SportController extends AbstractController
{
    #[Route('/s/{slug}/{page}', name: 'sport', defaults: ['page' => 1], requirements: ['page' => '\d+'])]
    public function detail(ProductRepository $productRepository, SportRepository $sportRepository, string $slug, int $page): Response
    {
        $productsPerPage = 12;

        $sport = $sportRepository->findOneBy(['slug' => $slug]);

        if (!$sport) {
            throw $this->createNotFoundException('La marca no existe.');
        }

        $totalProducts = $productRepository->count(['sport' => $sport]);
        $totalPages = ceil($totalProducts / $productsPerPage);

        $offset = ($page - 1) * $productsPerPage;

        $products = $productRepository->findBy(
            ['sport' => $sport],
            null,
            $productsPerPage,
            $offset
        );

        shuffle($products);

        return $this->render('sport/detail.html.twig', [
            'title' => $sport->getName(),
            'sport' => $sport,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'products' => $products,
        ]);
    }
}
