<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Doctrine\ORM\EntityManagerInterface;

class CategoryCrudController extends AbstractCrudController
{
    public function __construct(#[Autowire('%kernel.project_dir%/public/uploads/images/products/categories')] string $categoryDirectory)
    {
        $this->categoryDirectory = $categoryDirectory;
    }

    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Categoria')
            ->setEntityLabelInPlural('Categorias')
            ->setSearchFields(['name', 'description'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('name', 'Nombre')->setRequired(true),
            IntegerField::new('views', 'Vistas')->onlyOnIndex(),
            TextEditorField::new('description', 'Descripcion'),
        ];
    }

    public function createEntity(string $entityFqcn)
    {
        $category = new Category();

        return $category;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityManager->persist($entityInstance);
        $entityManager->flush();

        $categoryDirectory = $this->createCategorytDirectory($entityInstance, $this->categoryDirectory);

        parent::persistEntity($entityManager, $entityInstance);
    }

    private function createCategorytDirectory($entityInstance, string $baseDirectory): string
    {
        $categoryDirectory = $baseDirectory . '/' . $entityInstance->getId();
        if (!is_dir($categoryDirectory)) {
            mkdir($categoryDirectory, 0777, true);
        }

        return $categoryDirectory;
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Category) {
            $genericCategory = $entityManager->getRepository(Category::class)->findOneBy(['name' => 'Sin Categoria']);

            if ($genericCategory) {
                foreach ($entityInstance->getProducts() as $product) {
                    $product->setCategory($genericCategory);
                }
            }
        }

        $categoryDirectoryDeleted = $this->deleteCategorytDirectory($entityInstance, $this->categoryDirectory);

        parent::deleteEntity($entityManager, $entityInstance);
    }

    private function deleteCategorytDirectory($entityInstance, string $baseDirectory): string
    {
        $categoryDirectory = $baseDirectory . '/' . $entityInstance->getId();
        if (is_dir($categoryDirectory)) {
            rmdir($categoryDirectory);
        }

        return $categoryDirectory;
    }

}
