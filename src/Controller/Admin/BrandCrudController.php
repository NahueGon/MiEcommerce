<?php

namespace App\Controller\Admin;

use App\Entity\Brand;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class BrandCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Brand::class;
    }
    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            ImageField::new('ImageUrl','Imagen')->hideOnForm(),
            TextField::new('name', 'Nombre')
                ->setRequired(true)
                ->setColumns(3)
                ->setHelp('Este campo es Obligatorio.'),
            TextField::new('img_brand', 'Imagen')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                'required' => false,
                'data_class' => null,
                ])
                ->setHelp('Sube una imagen de la marca.')
                ->hideOnindex()
                ->setColumns(3),
            FormField::addPanel(''),
            TextEditorField::new('description', 'Descripcion')->setColumns(6),
        ];
    }
    
}
