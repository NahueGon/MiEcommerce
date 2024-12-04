<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\BrandRepository;
use App\Repository\SportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        BrandRepository $brandRepository,
        SportRepository $sportRepository
    ): Response {
        $products = $productRepository->findAll();  // Obtén más de 10 si deseas mezclar más productos
        shuffle($products);  // Mezcla los productos aleatoriamente
        $products = array_slice($products, 0, 10);  // Toma solo los primeros 10

        $discountedProducts = $productRepository->findWithDiscounts();
        shuffle($discountedProducts);  // Mezcla los productos aleatoriamente
        $discountedProducts = array_slice($discountedProducts, 0, 10);

        // Obtén todas las categorías con imágenes válidas
        $categories = $categoryRepository->findAll();

        // Filtra categorías con imágenes
        $firstCategories = array_filter($categories, function ($category) {
            return $category->getParents()->isEmpty() && $category->getName() !== 'Sin Especificar';
        });

        $subCategories = array_filter($categories, function ($category) {
            return !$category->getParents()->isEmpty() && !$category->getSubCategories()->isEmpty();
        });

        $firstCategories = array_slice($firstCategories, 0, 3);

        $brands = $brandRepository->findAll();

        $sports = $sportRepository->findAll();

        return $this->render('home/index.html.twig', [
            'title' => 'Inicio',
            'products' => $products,
            'discountedProducts' => $discountedProducts,
            'categories' => $firstCategories,
            'subcategories' => $subCategories,
            'brands' => $brands,
            'sports' => $sports,
        ]);
    }
}