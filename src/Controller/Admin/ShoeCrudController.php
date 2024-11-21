<?php

namespace App\Controller\Admin;

use App\Entity\Shoe;
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
            ->renderExpanded(false) // Cambia a true para mostrar como checkboxes en lugar de un menú desplegable
            ->setColumns(3);
        $sizeField =  ChoiceField::new('size', 'Talle')
            ->setChoices([
                'Talle 35.5 (ARG)' => '35',
                'Talle 36 (ARG)' => '36',
                'Talle 36.5 (ARG)' => '36.5',
                'Talle 37 (ARG)' => '37',
                'Talle 37.5 (ARG)' => '37.5',
                'Talle 38 (ARG)' => '38',
                'Talle 38.5 (ARG)' => '38.5',
                'Talle 39 (ARG)' => '39',
                'Talle 39.5 (ARG)' => '39.5',
                'Talle 40 (ARG)' => '40',
                'Talle 40.5 (ARG)' => '40.5',
                'Talle 41 (ARG)' => '41',
                'Talle 41.5 (ARG)' => '41.5',
                'Talle 42 (ARG)' => '42',
                'Talle 42.5 (ARG)' => '42.5',
                'Talle 43 (ARG)' => '43',
                'Talle 43.5 (ARG)' => '43.5',
                'Talle 44 (ARG)' => '44',
                'Talle 44.5 (ARG)' =>' 44.5',
                'Talle 45 (ARG)' => '45',
                'Talle 45.5 (ARG)' => '45.5',
                'Talle 46 (ARG)' => '46',
                'Talle 46.5 (ARG)' => '46.5',
                'Talle 47 (ARG)' => '47',
                'Talle 47.5 (ARG)' => '47.5',
                'Talle 48 (ARG)' => '48',
                'Talle 48.5 (ARG)' => '48.5',
            ])
            ->setColumns(3);

        array_splice($fields, 10, 0, [$colorField, $sizeField]);

        return $fields;    
    }
}
