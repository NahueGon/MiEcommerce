<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\SportRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    public function __construct(
        CategoryRepository $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    #[Route('/c/todos/{page}', name: 'categories', defaults: ['page' => 1], requirements: ['page' => '\d+'])]
    public function index(CategoryRepository $categoryRepository, int $page): Response
    {
        $categoriesPerPage = 12;
        $totalCategories = $categoryRepository->count([]);
        $totalPages = ceil($totalCategories / $categoriesPerPage);

        $offset = ($page - 1) * $categoriesPerPage;

        $categories = $categoryRepository->findBy([], null, $categoriesPerPage, $offset);

        shuffle($categories);

        return $this->render('category/index.html.twig', [
            'title' => 'Todas las Categorias',
            'categories' => $categories,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/c/{slug}', name: 'show_category_slug')]
    #[Route('/c/{parentSlug}/{slug}', name: 'show_category_parent')]
    #[Route('/c/{grandparentSlug}/{parentSlug}/{slug}', name: 'show_category')]
    public function show(
        ?string $grandparentSlug,
        ?string $parentSlug,
        string $slug,
        SportRepository $sportRepository,
        ProductRepository $productRepository,
    ): Response {
        $grandparent = null;
        $parent = null;
        
        if ($grandparentSlug) {
            $grandparent = $this->categoryRepository->findOneBy(['slug' => $grandparentSlug]);
            
            if (!$grandparent) {
                throw $this->createNotFoundException('Categoría abuelo no encontrada');
            }
        }
        
        if ($parentSlug) {
            $parent = $this->categoryRepository->findOneBy(['slug' => $parentSlug]);
            
            if (!$parent) {
                throw $this->createNotFoundException('Categoría padre no encontrada');
            }
        }

        $category = $this->categoryRepository->findOneBy(['slug' => $slug]);

        $sports = $sportRepository->findAll();
        shuffle($sports);
        
        $discountedProducts = $productRepository->findWithDiscounts();
        shuffle($discountedProducts);
        $discountedProducts = array_slice($discountedProducts, 0, 1);

        $products = $productRepository->findBy(['gender' => $slug]);
        shuffle($products);  // Mezcla los productos aleatoriamente
        $products = array_slice($products, 0, 10); 

        if (!$category) {
            throw $this->createNotFoundException('Categoría no encontrada');
        }
        return $this->render('category/detail.html.twig', [
            'category' => $category,
            'parent' => $parent,
            'grandparent' => $grandparent,
            'sports' => $sports,
            'products' => $products,
            'discountedProducts' => $discountedProducts,
        ]);
    }
}
