<?php

namespace App\Twig;

use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getCategories', [$this, 'getCategories']),
        ];
    }

    public function getCategories()
    {
        return $this->categoryRepository->findBy([], ['name' => 'ASC']);
    }

    public function renderCategories($categories)
    {
        $filteredCategories = [];
        foreach ($categories as $category) {
            if ($category->getName() === 'Sin Especificar') {
                continue;
            }

            $subCategories = $this->getSubCategories($category);
            $filteredCategories[] = [
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'subCategories' => $subCategories,
            ];
        }

        return $filteredCategories;
    }

    private function getSubCategories($category)
    {
        $subCategories = [];

        foreach ($category->getSubCategories() as $subCategory) {
            if ($subCategory->getName() === 'Sin Especificar') {
                continue; 
            }

            $parent = $subCategory->getParents()->first(); // Obtén el primer padre
            $subCategories[] = [
                'name' => $subCategory->getName(),
                'slug' => $subCategory->getSlug(),
                'parent' => $parent ? $parent->getName() : null, // Agrega el nombre del padre si existe
            ];
        }

        return $subCategories;
    }
}