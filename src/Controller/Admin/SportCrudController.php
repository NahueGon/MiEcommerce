<?php

namespace App\Controller\Admin;

use App\Entity\Sport;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class SportCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sport::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Deporte')
            ->setEntityLabelInPlural('Deportes')
            ->setSearchFields(['name', 'description'])
            ->setDefaultSort(['id' => 'ASC']);
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
            TextField::new('img_sport', 'Imagen')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                'required' => false,
                'data_class' => null,
                ])
                ->setHelp('Sube una imagen del deporte.')
                ->hideOnindex()
                ->setColumns(3),
            FormField::addPanel(''),
            TextEditorField::new('description', 'Descripcion')->setColumns(6),
        ];
    }

}
