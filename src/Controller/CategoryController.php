<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
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

    // #[Route('/c', name: 'app_category')]
    // public function index(): Response
    // {

    //     return $this->render('category/index.html.twig', [
    //         'controller_name' => 'CategoryController',
    //     ]);
    // }

    #[Route('/c/{slug}', name: 'show_category_slug')]
    #[Route('/c/{parentSlug}/{slug}', name: 'show_category_parent')]
    #[Route('/c/{grandparentSlug}/{parentSlug}/{slug}', name: 'show_category')]
    public function showCategory(?string $grandparentSlug, ?string $parentSlug, string $slug): Response
    {
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
        
        if (!$category) {
            throw $this->createNotFoundException('Categoría no encontrada');
        }
        return $this->render('category/detail.html.twig', [
            'category' => $category,
            'parent' => $parent,
            'grandparent' => $grandparent,
        ]);
    }
}
