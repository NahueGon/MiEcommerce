<?php

namespace App\Controller\Admin;

use App\Entity\Clothing;
use App\Entity\Product;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ClothingCrudController extends AbstractProductCrudController
{
    private EntityManagerInterface $em;
    private string $categoriesDirectory;
    private RequestStack $requestStack;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/images/products/categories')] string $categoriesDirectory,
        EntityManagerInterface $em,
        RequestStack $requestStack
    ) {
        parent::__construct($categoriesDirectory, $em, $requestStack);
        $this->categoriesDirectory = $categoriesDirectory;
        $this->requestStack = $requestStack;
    }

    public static function getEntityFqcn(): string
    {
        return Clothing::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Ropa')
            ->setEntityLabelInPlural('Ropa')
            ->setSearchFields(['name', 'description'])
            ->setDefaultSort(['gender' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = parent::configureFields($pageName);

        $colorField = TextField::new('color', 'Color')->setColumns(3);
        $sizeField =  ChoiceField::new('size', 'Talle')
        ->setChoices([
            'XS' => 'XS',
            'S' => 'S',
            'M' => 'M',
            'L' => 'L',
            'XL' => 'XL',
        ])
        ->setColumns(3);

        array_splice($fields, 10, 0, [$colorField, $sizeField]);

        return $fields;    
    }
}
