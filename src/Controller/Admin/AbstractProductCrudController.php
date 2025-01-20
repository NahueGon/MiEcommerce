<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Brand;
use App\Entity\Sport;
use App\Entity\Shoe;
use App\Entity\Clothing;
use App\Entity\Accessory;
use App\Repository\CategoryRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractProductCrudController extends AbstractCrudController
{
    private EntityManagerInterface $em;
    private string $categoriesDirectory;
    private RequestStack $requestStack;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/images/products/categories')] string $categoriesDirectory,
        EntityManagerInterface $em,
        RequestStack $requestStack
    ) {
        $this->em = $em;
        $this->categoriesDirectory = $categoriesDirectory;
        $this->requestStack = $requestStack;
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Producto')
            ->setEntityLabelInPlural('Productos')
            ->setSearchFields(['name', 'description'])
            ->setDefaultSort(['gender' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->onlyOnIndex(),
            ImageField::new('ImageUrl','Imagen')
                ->onlyOnIndex(),
            TextField::new('name', 'Nombre')
                ->setRequired(true)
                ->setColumns(3)
                ->setHelp('Este campo es Obligatorio.'),
            AssociationField::new('category', 'Categoría')
                ->setColumns(3)
                ->setFormTypeOptions([
                    'query_builder' => function() {
                        return $this->em->getRepository(Category::class)
                                  ->createQueryBuilderForCategoriesWithoutSubCategories();
                    },
                ]),
                AssociationField::new('brand', 'Marca')
                ->setColumns(3),
            FormField::addPanel(''),
                ChoiceField::new('gender', 'Género')
                ->setColumns(3)
                ->setRequired(false)
                ->setChoices(function() {
                    $choices = [];
                    
                    $categoriesWithoutParents = $this->em
                    ->getRepository(Category::class)
                    ->createQueryBuilderForCategoriesWithoutParents()
                    ->getQuery()
                    ->getResult();
                    
                    if ($categoriesWithoutParents) {
                        foreach ($categoriesWithoutParents as $category) {
                            $choices[$category->getName()] = $category->getName();
                        }
                    } else {
                        $choices['Sin categoría'] = 'Sin categoría';
                    }
                    
                    return $choices;
                }),
            AssociationField::new('sport', 'Deporte')
                ->setColumns(3),
            MoneyField::new('price_list', 'Precio de Lista')
                ->setCurrency('ARS')
                ->setRequired(true)
                ->setColumns(3)
                ->setHelp('Este campo es Obligatorio.'),
            FormField::addPanel(''),
            PercentField::new('discount', 'Descuento (%)')
                ->setStoredAsFractional(false) // False si trabajas con porcentajes como 20 (en lugar de 0.2)
                ->setHelp('Introduce el porcentaje de descuento (ej. 20 para 20%).')
                ->setColumns(3)
                ->setRequired(false),
            TextField::new('img_product', 'Imagen del Producto')
                ->formatValue(function ($value, $entity) {
                    if ($value) {
                        // Aquí obtenemos la URL completa de la imagen
                        $imageUrl = '/uploads/images/products/categories/' . $entity->getCategory()->getId() . '/' . $value;
    
                        // Mostramos la imagen como HTML
                        return sprintf('<img src="%s" alt="Producto" style="max-height: 150px;"/>', $imageUrl);
                    }
    
                    return 'No hay imagen disponible';
                })
                ->renderAsHtml()
                ->onlyOnForms()
        ];

        if (Crud::PAGE_NEW === $pageName) {
            $fields[] = FormField::addPanel('');
            $fields[] = 
            TextField::new('img_product', 'Imagen')
                ->setColumns(3)
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'required' => true,
                    'data_class' => null,
                ])
                ->setHelp('Sube una imagen para el Producto.');
        
        } elseif (Crud::PAGE_EDIT === $pageName){
            $fields[] =
            TextField::new('img_product', 'Imagen')
                ->setColumns(3)
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'required' => false,
                    'data_class' => null,
                ])
                ->setHelp('Sube una imagen para el Producto.');
        }

        $fields[] = 
            TextEditorField::new('description', 'Descripcion')->setColumns(6);
            IntegerField::new('views', 'Vistas')->onlyOnIndex();

        return $fields;
    }

    public function createEntity(string $entityFqcn)
    {
        switch ($entityFqcn) {
            case Shoe::class:
                $product = new Shoe();
                break;
            case Clothing::class:
                $product = new Clothing();
                break;
            case Accessory::class:
                $product = new Accessory();
                break;
            default:
                throw new \InvalidArgumentException('Unsupported product type: ' . $entityFqcn);
        }

        $product->setCreatedAt(new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires')));

        return $product;
    }

    public function persistEntity(EntityManagerInterface $em, $product): void
    {
        if ($product instanceof Product) {
            $this->sanitizeProduct($product);
            $this->createCategory($product);
            $this->categoryDirectoryExists($product);
            
            if ($product instanceof Shoe) {
                $productType = 'Shoe'; 
            } elseif ($product instanceof Clothing) {
                $productType = 'Clothing'; 
            } elseif ($product instanceof Accessory) {
                $productType = 'Accessory'; 
            }

            $request = $this->requestStack->getCurrentRequest();
            $imgProductFile = $request->files->get($productType)['img_product'];
            
            try {
                $em->persist($product);
                $em->flush();
                
                $slug = $product->category() . '-' . $product->name() . '-' . $product->id();
                $product->setSlug($slug);

                $this->imageUpload($product, $imgProductFile);
                flash()
                ->title('Exito!')
                ->option('timeout', 3000)
                ->success('Producto creado!' );
                
                parent::persistEntity($em, $product);
            }catch(\Exception $e){
                flash()
                    ->option('timeout', 3000)
                    ->error('Error al guardar el producto' );

                $em->remove($product);
                $em->flush();
            }
        }
    }

    public function sanitizeProduct(Product $product): void{
        $name = strip_tags($product->getName());
        $product->setName($name);
        
        if ($description = $product->getDescription()) {
            $sanitizedDescription = preg_replace('/\s+/', ' ', strip_tags(str_replace('&nbsp;', ' ', $description)));
            $product->setDescription($sanitizedDescription);
        }
    }

    private function categoryDirectoryExists(Product $product): void
    {
        $categoryId = $product->getCategory()->getId();
        $categoryDirectory = $this->categoriesDirectory . '/' . $categoryId;     
        
        if (!is_dir($categoryDirectory)) {
            mkdir($categoryDirectory, 0777, true);
        }
    }

    private function createCategory(Product $product): void
    {
        if(empty($product->getCategory())){
            $defaultCategory = $this->em->getRepository(Category::class)
                ->findOneBy(['name' => 'Sin especificar']);

            if (!$defaultCategory) {
                $defaultCategory = new Category();
                $defaultCategory->setName('Sin especificar');

                $this->em->persist($defaultCategory);
                $this->em->flush();
            }

            $product->setCategory($defaultCategory);
        }
    }

    public function updateEntity(EntityManagerInterface $em, $product): void
    {
        if ($product instanceof Product) {
            $this->sanitizeProduct($product);
            $this->createCategory($product);
            $this->categoryDirectoryExists($product);
            $product->setUpdatedAt(new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires')));

            $originalData = $em->getUnitOfWork()->getOriginalEntityData($product);

            $oldName = $originalData['name'];
            $oldFilename = $originalData['img_product'];
            $oldCategoryId = $originalData['category_id'];

            if ($product instanceof Shoe) {
                $productType = 'Shoe'; 
            } elseif ($product instanceof Clothing) {
                $productType = 'Clothing'; 
            } elseif ($product instanceof Accessory) {
                $productType = 'Accessory'; 
            }
            
            $request = $this->requestStack->getCurrentRequest();
            $imgProductFile = $request->files->get($productType)['img_product'];

            $newCategoryId = $product->getCategory()->getId();
            $newName = $product->getName();

            try {
                $em->persist($product);
                $em->flush();

                if ($imgProductFile instanceof UploadedFile) {
                    $this->imageDelete($product, $oldFilename, $oldCategoryId);
                    $this->imageUpload($product, $imgProductFile);
                } else {
                    if ($newName != $oldName || $newCategoryId != $oldCategoryId) {
                        $this->imageRename($product, $oldFilename, $oldCategoryId);
                    } else {
                        $product->setImgProduct($oldFilename);
                    }
                }

                flash()
                    ->title('Exito!')
                    ->option('timeout', 3000)
                    ->success('Producto Editado con exito!' );

                parent::updateEntity($em, $product);
            }catch(\Exception $e){
                flash()
                    ->option('timeout', 3000)
                    ->error('Error al editar el producto' );
            }
        }
    }

    public function deleteEntity(EntityManagerInterface $em, $product): void
    {
        $filename = $product->getImgProduct();
        $categoryId = $product->getCategory()->getId();
        
        try {
            $this->imageDelete($product, $filename, $categoryId);
            flash()
                ->title('Exito!')
                ->option('timeout', 3000)
                ->success('Producto Eliminado Correctamente.');

            parent::deleteEntity($em, $product);
        } catch (Exception $e) {
            flash()
                ->title('Exito!')
                ->option('timeout', 3000)
                ->success('Error al eliminar el archivo.');
        }
    }

    private function imageUpload(Product $product, $imgProductFile): void
    {
        $categoryId = $product->getCategory()->getId();
        $newFilename = sprintf('%d_%s.%s',
            $product->getId(),
            $product->getName(),
            $imgProductFile->guessExtension()
        );

        $productImageDirectory = $this->categoriesDirectory . '/' . $categoryId . '/' . $newFilename;

        try {
            $this->uploadAndResizeImage($imgProductFile, $productImageDirectory);
            $product->setImgProduct($newFilename);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error al subir la imagen: ' . $e->getMessage());
        }
    }

    private function uploadAndResizeImage(UploadedFile $imgProductFile, string $targetPath, int $size = 600): void
    {
        $originalImage = imagecreatefromstring(file_get_contents($imgProductFile->getPathname()));
        list($originalWidth, $originalHeight) = getimagesize($imgProductFile->getPathname());

        $cropSize = min($originalWidth, $originalHeight);

        $newImage = imagecreatetruecolor($size, $size);

        $xOffset = ($originalWidth - $cropSize) / 2;
        $yOffset = ($originalHeight - $cropSize) / 2;

        imagecopyresampled($newImage, $originalImage, 0, 0, $xOffset, $yOffset, $size, $size, $cropSize, $cropSize);

        imagejpeg($newImage, $targetPath, 100);

        imagedestroy($originalImage);
        imagedestroy($newImage);
    }
    
    public function imageDelete($product, $filename, $categoryId)
    {
        $productImageDirectory = $this->categoriesDirectory . '/' . $categoryId . '/' . $filename;
        
        if (!file_exists($productImageDirectory)) {
            flash()
                ->title('Información')
                ->option('timeout', 3000)
                ->info('El archivo no existe en el sistema.');

                return $this->redirect($this->generateUrl('admin'));
        }

        try {
            unlink($productImageDirectory);
        } catch (\Exception $e) {
            flash()
                ->title('Error')
                ->option('timeout', 3000)
                ->error('Error al eliminar la imagen: ' . $e->getMessage());
        }
    }

    public function imageRename($product, $filename, $categoryId = null): void
    {
        if ($categoryId === null) {
            $categoryId = $product->getCategory()->getId();
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $newFilename = sprintf('%d_%s.%s',
            $product->getId(),
            $product->getName(),
            $extension
        );  

        $oldFilePath = $this->categoriesDirectory . '/' . $categoryId . '/' . $filename;
        $newFilePath = $this->categoriesDirectory . '/' . $product->getCategory()->getId() . '/' . $newFilename;

        if (!file_exists($oldFilePath)) {
            flash()
                ->option('timeout', 3000)
                ->info('El archivo antiguo no existe.');
        }

        try {
            $product->setImgProduct($newFilename);

            rename($oldFilePath, $newFilePath);
        } catch (\Exception $e) {
            flash()
                ->option('timeout', 3000)
                ->error('Error al renombrar el archivo: ' . $e->getMessage());
        }
    }
}