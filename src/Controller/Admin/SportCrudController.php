<?php

namespace App\Controller\Admin;

use App\Entity\Sport;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

class SportCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sport::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('name', 'Nombre')
                ->setRequired(true)
                ->setColumns(3)
                ->setHelp('Este campo es Obligatorio.'),
            FormField::addPanel(''),
            TextEditorField::new('description', 'Descripcion')->setColumns(6),
        ];
    }

}
