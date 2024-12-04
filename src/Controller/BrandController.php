<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\BrandRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BrandController extends AbstractController
{
    #[Route('/b/{slug}/{page}', name: 'brand', defaults: ['page' => 1], requirements: ['page' => '\d+'])]
    public function detail(ProductRepository $productRepository, BrandRepository $brandRepository, string $slug, int $page): Response
    {
        $productsPerPage = 12;

        $brand = $brandRepository->findOneBy(['slug' => $slug]);

        if (!$brand) {
            throw $this->createNotFoundException('La marca no existe.');
        }

        $totalProducts = $productRepository->count(['brand' => $brand]);
        $totalPages = ceil($totalProducts / $productsPerPage);

        $offset = ($page - 1) * $productsPerPage;

        $products = $productRepository->findBy(
            ['brand' => $brand],
            null,
            $productsPerPage,
            $offset
        );

        return $this->render('brand/detail.html.twig', [
            'title' => $brand->getName(),
            'brand' => $brand,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'products' => $products,
        ]);
    }
} 
