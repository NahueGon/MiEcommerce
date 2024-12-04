<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/p/todos/{page}', name: 'products', defaults: ['page' => 1], requirements: ['page' => '\d+'])]
    public function index(ProductRepository $productRepository, int $page): Response
    {
        $productsPerPage = 12;
        $totalProducts = $productRepository->count([]);
        $totalPages = ceil($totalProducts / $productsPerPage);

        $offset = ($page - 1) * $productsPerPage;

        $products = $productRepository->findBy([], null, $productsPerPage, $offset);

        // shuffle($products);  // Mezcla los productos aleatoriamente
        // $products = array_slice($products, 0, 10);  // Toma solo los primeros 10

        return $this->render('product/index.html.twig', [
            'title' => 'Todos los Productos',
            'products' => $products,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);

    }

    #[Route('/p/{slug}', name: 'show_product')]
    public function show(
        string $slug,
        ProductRepository $productRepository,
    ): Response{

        $product = $productRepository->findOneBy(['slug' => $slug]);

        return $this->render('product/detail.html.twig', [
            'product' => $product
        ]);
    }
}
