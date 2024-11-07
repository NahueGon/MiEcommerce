<?php

namespace App\Controller\Admin;

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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductCrudController extends AbstractCrudController
{
    private EntityManagerInterface $em;
    private $slugger;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/images/products/categories')] string $categoriesDirectory,
        EntityManagerInterface $em,
        SluggerInterface $slugger
        ) {
        $this->em = $em;
        $this->categoriesDirectory = $categoriesDirectory;
        $this->slugger = $slugger;
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
            ->setDefaultSort(['id' => 'DESC']);
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
            AssociationField::new('category', 'Categoria')
                ->setColumns(3),
            IntegerField::new('stock', 'Cantidad')
                ->setColumns(3),
            FormField::addPanel(''),
            AssociationField::new('brand', 'Marca')
                ->setColumns(3),
            ChoiceField::new('gender', 'Género')
                ->setColumns(3)
                ->setRequired(false)
                ->setChoices(function() {
                    $categoryRepository = $this->em->getRepository(Category::class);

                    $categoriesWithoutParents = $categoryRepository->createQueryBuilder('c')
                        ->leftJoin('c.parents', 'p')
                        ->where('p.id IS NULL')
                        ->orderBy('c.name', 'ASC')
                        ->getQuery()
                        ->getResult();
                    
                    $choices = [];
                    foreach ($categoriesWithoutParents as $category) {
                        $choices[$category->getName()] = $category->getId();
                    }

                    return $choices;
                }),
            AssociationField::new('sport', 'Deporte')
                ->setColumns(3),
            FormField::addPanel(''),
            MoneyField::new('price_list', 'Precio de Lista')
                ->setCurrency('ARS')
                ->setRequired(true)
                ->setColumns(3)
                ->setHelp('Este campo es Obligatorio.'),
            MoneyField::new('price_sale', 'Precio Final')
                ->setCurrency('ARS')
                ->setColumns(3),
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
            $fields[] = FormField::addPanel('');
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
            DateTimeField::new('created_at', 'Fecha de Creacion')->onlyOnIndex();
            DateTimeField::new('updated_at', 'Fecha de Actualizacion')->onlyOnIndex();

        return $fields;
    }

    public function createEntity(string $entityFqcn)
    {
        $product = new Product();
        $product->setCreatedAt(new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires')));

        return $product;
    }

    public function persistEntity(EntityManagerInterface $em, $product): void
    {
        if ($product instanceof Product) {
            $this->sanitizeProduct($product);
            $this->handleCreateCategory($em, $product);
            $this->ensureCategoryDirectoryExists($product);

            $imgProductFile = $this->getContext()->getRequest()->files->get('Product')['img_product'];

            try {
                $em->persist($product);
                $em->flush();

                $this->handleImageUpload($product, $imgProductFile);
                
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
       
        if ($brand = $product->getBrand()) {
            $product->setBrand(strip_tags($brand));
        }

        if ($description = $product->getDescription()) {
            $sanitizedDescription = preg_replace('/\s+/', ' ', strip_tags(str_replace('&nbsp;', ' ', $description)));
            $product->setDescription($sanitizedDescription);
        }
    }

    private function ensureCategoryDirectoryExists(Product $product): void
    {
        $categoryId = $product->getCategory()->getId();
        $categoryDirectory = $this->categoriesDirectory . '/' . $categoryId;     

        if (!is_dir($categoryDirectory)) {
            mkdir($categoryDirectory, 0777, true);
        }
    }

    private function handleImageUpload(Product $product, $imgProductFile): void
    {
        $categoryId = $product->getCategory()->getId();
        $newFilename = sprintf('%d_%s.%s',
            $product->getId(),
            $product->getName(),
            $imgProductFile->guessExtension()
        );

        $productImageDirectory = $this->categoriesDirectory . '/' . $categoryId . '/' . $newFilename;

        try {
            $this->resizeAndSaveImage($imgProductFile, $productImageDirectory);
            $product->setImgProduct($newFilename);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error al subir la imagen: ' . $e->getMessage());
        }
    }

    private function handleCreateCategory(EntityManagerInterface $em, Product $product): void
    {
        if(empty($product->getCategory())){
            $defaultCategory = $em->getRepository(Category::class)
                ->findOneBy(['name' => 'Sin especificar']);

            if (!$defaultCategory) {
                $defaultCategory = new Category();
                $defaultCategory->setName('Sin especificar');

                $em->persist($defaultCategory);
                $em->flush();
            }

            $product->setCategory($defaultCategory);
        }
    }
    
    private function resizeAndSaveImage(UploadedFile $imgProductFile, string $targetPath, int $size = 300): void
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

    public function updateEntity(EntityManagerInterface $em, $product): void
    {
        if ($product instanceof Product) {
            $this->sanitizeProduct($product);
            $this->handleCreateCategory($em, $product);
            $this->ensureCategoryDirectoryExists($product);

            $imgProductFile = $this->getContext()->getRequest()->files->get('Product')['img_product'];

            $originalData = $em->getUnitOfWork()->getOriginalEntityData($product);

            $oldCategoryId = $originalData['category_id'];
            $newCategoryId = $product->getCategory()->getId();

            $oldName = $originalData['name'];
            $oldFilename = $originalData['img_product'];

            $newName = $product->getName();

            try {
                $em->persist($product);
                $em->flush();

                if ($imgProductFile instanceof UploadedFile) {
                    $this->handleImageDelete($product, $oldFilename, $oldCategoryId);
                    $this->handleImageUpload($product, $imgProductFile);
                } else {
                    if ($newName != $oldName || $newCategoryId != $oldCategoryId) {
                        $this->handleImageRename($product, $oldFilename, $oldCategoryId);
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

    public function handleImageRename($product, $filename, $categoryId = null): void
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
    
    public function handleImageDelete($product, $filename, $categoryId)
    {
        $productImageDirectory = $this->categoriesDirectory . '/' . $categoryId . '/' . $filename;
        
        if (!file_exists($productImageDirectory)) {
            flash()
                ->title('Información')
                ->option('timeout', 3000)
                ->info('El archivo no existe en el sistema.');

                return $this->redirect($this->generateUrl('admin'));
        }

        unlink($productImageDirectory);
    }

    public function deleteEntity(EntityManagerInterface $em, $product): void
    {
        $filename = $product->getImgProduct();
        $categoryId = $product->getCategory()->getId();
        
        try {
            $this->handleImageDelete($product, $filename, $categoryId);
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
}