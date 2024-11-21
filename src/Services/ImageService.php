<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ImageService
{
    private string $categoriesDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%/public/uploads/images/products/categories')] string $categoriesDirectory,)
    {
        $this->categoriesDirectory = $categoriesDirectory;
    }
}