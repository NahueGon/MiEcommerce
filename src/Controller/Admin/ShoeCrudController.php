<?php

namespace App\Controller\Admin;

use App\Entity\Shoe;
use App\Entity\Product;
use App\Entity\Category;
use App\Form\SizeStockType;
use App\Repository\CategoryRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
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

class ShoeCrudController extends AbstractProductCrudController
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
        return Shoe::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Zapatilla')
            ->setEntityLabelInPlural('Zapatillas')
            ->setSearchFields(['name', 'description'])
            ->setDefaultSort(['gender' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        
        $fields = parent::configureFields($pageName);

        $colorField = ChoiceField::new('color', 'Colores')
            ->setChoices([
                'Rojo' => 'red',
                'Azul' => 'blue',
                'Verde' => 'green',
                'Amarillo' => 'yellow',
                'Naranja' => 'orange',
                'Rosa' => 'pink',
                'Morado' => 'purple',
                'Negro' => 'black',
                'Blanco' => 'white',
                'Gris' => 'gray',
                'Cian' => 'cyan',
                'Magenta' => 'magenta',
                'Marrón' => 'brown',
                'Turquesa' => 'turquoise',
                'Oliva' => 'olive',
                'Beige' => 'beige',
                'Dorado' => 'gold',
                'Plateado' => 'silver',
                'Lima' => 'lime',
                'Violeta' => 'violet',
                'Marfil' => 'ivory',
                'Lavanda' => 'lavender',
                'Chocolate' => 'chocolate',
                'Azul Marino' => 'navy',
                'Coral' => 'coral',
                'Verde Azulado' => 'teal',
                'Fucsia' => 'fuchsia',
                'Aguamarina' => 'aquamarine',
                'Perla' => 'pearl',
                'Granate' => 'maroon',
                'Mostaza' => 'mustard',
                'Amatista' => 'amethyst',
                'Cobre' => 'copper',
                'Celeste' => 'lightblue',
            ])
            ->allowMultipleChoices(true)
            ->renderExpanded(false)
            ->setColumns(3);

        $sizeField = CollectionField::new('sizes', 'Talles')
            ->setEntryType(SizeStockType::class)
            ->allowAdd()
            ->allowDelete()
            ->hideOnIndex()
            ->setColumns(3);

        $totalSizeField = IntegerField::new('totalStock', 'Total Stock')
            ->onlyOnIndex();

        $panelField = FormField::addPanel('');

        array_splice($fields, 11, 0, [$colorField, $sizeField, $totalSizeField, $panelField]);

        return $fields;    
    }
}
