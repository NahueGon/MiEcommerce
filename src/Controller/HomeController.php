<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $products = $productRepository->findBy([], [], 50);  // Obtén más de 10 si deseas mezclar más productos
        shuffle($products);  // Mezcla los productos aleatoriamente
        $products = array_slice($products, 0, 10);  // Toma solo los primeros 10

        // Obtén todas las categorías con imágenes válidas
        $categories = $categoryRepository->findAll();

        // Filtra categorías con imágenes
        $categories = array_filter($categories, function ($category) {
            return $category->getParents()->isEmpty() && $category->getName() !== 'Sin Especificar';
        });

        $categories = array_slice($categories, 0, 3);

        return $this->render('home/index.html.twig', [
            'title' => 'Inicio',
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}