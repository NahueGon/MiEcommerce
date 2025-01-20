<?php

namespace App\Controller\Admin;

use App\Entity\SizeStock;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class SizeStockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SizeStock::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Talle y Cantidad')
            ->setEntityLabelInPlural('Talles y Cantidades')
            ->setSearchFields(['size', 'stock']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('shoe', 'Producto'),  // Relación con el producto (Shoe)
            NumberField::new('size', 'Talle'),
            IntegerField::new('stock', 'Cantidad en stock'),
        ];
    }
}
