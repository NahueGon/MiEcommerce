<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;

class CategoryCrudController extends AbstractCrudController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/images/products/categories')] string $categoryDirectory
    ) {
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
            ->setDefaultSort(['parents' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            ImageField::new('ImageUrl','Imagen')->hideOnForm(),
            TextField::new('name', 'Nombre')->setRequired(true),
            IntegerField::new('views', 'Vistas')->onlyOnIndex(),
            AssociationField::new('parents', 'Categoría Padre')
                ->setRequired(false)
                ->formatValue(function ($value, $entity) {
                    // Convertir los padres en una lista de nombres
                    return implode(', ', $entity->getParents()->map(function ($parent) {
                        return $parent->getName(); // Mostrar los nombres de los padres
                    })->toArray());
                }),
            TextField::new('slug', 'Slug')->onlyOnIndex(),
            TextField::new('img_category', 'Imagen')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                'required' => false,
                'data_class' => null,
                ])
                ->setHelp('Sube una imagen de la categoria.')
                ->hideOnindex(),
            TextEditorField::new('description', 'Descripcion')
        ];
    }

    public function createEntity(string $entityFqcn)
    {
        $category = new Category();

        return $category;
    }

    public function persistEntity(EntityManagerInterface $em, $category): void
    {
        $this->sanitizeCategory($category);

        $imgCategoryFile = $this->getContext()->getRequest()->files->get('Category')['img_category'];

        try {
            $em->persist($category);
            $em->flush();

            $categoryDirectory = $this->createCategoryDirectory($category, $this->categoryDirectory);

            if ($imgCategoryFile) {
                $this->handleImageUpload($category, $imgCategoryFile);
            }

            parent::persistEntity($em, $category);
        }catch(\Exception $e){
            flash()
                ->option('timeout', 3000)
                ->error('Error al crear la categoria' );
            throw new \RuntimeException('Error al subir la imagen: ' . $e->getMessage());
        }
    }

    public function sanitizeCategory(Category $category): void{
        $name = strip_tags($category->getName());
        $category->setName($name);
        $category->setSlug($name);

        if ($description = $category->getDescription()) {
            $sanitizedDescription = preg_replace('/\s+/', ' ', strip_tags(str_replace('&nbsp;', ' ', $description)));
            $category->setDescription($sanitizedDescription);
        }
    }

    private function createCategoryDirectory($category, string $baseDirectory): string
    {
        $categoryDirectory = $baseDirectory . '/' . $category->getId();
        if (!is_dir($categoryDirectory)) {
            mkdir($categoryDirectory, 0777, true);
        }

        $this->createCategoryImageDirectory($category, $categoryDirectory);

        return $categoryDirectory;
    }

    private function createCategoryImageDirectory($category, string $baseDirectory): string
    {
        $categoryImageDirectory = $baseDirectory . '/' . 'CategoryProfileImage';
        if (!is_dir($categoryImageDirectory)) {
            mkdir($categoryImageDirectory, 0777, true);
        }

        return $categoryImageDirectory;
    }

    public function updateEntity(EntityManagerInterface $em, $category): void
    {
        if ($category instanceof Category) {
            $this->sanitizeCategory($category);

            $imgCategoryFile = $this->getContext()->getRequest()->files->get('Category')['img_category'];

            $originalData = $em->getUnitOfWork()->getOriginalEntityData($category);

            $oldName = $originalData['name'];
            $oldFilename = $originalData['img_category'];
            $newName = $category->getName();

            try {
                $em->persist($category);
                $em->flush();

                if ($imgCategoryFile instanceof UploadedFile) {
                    if ($oldFilename) {
                        $this->handleImageDelete($category, $oldFilename);
                    }
                    $this->createCategoryDirectory($category, $this->categoryDirectory);
                    $this->handleImageUpload($category, $imgCategoryFile);
                } else {
                    if ($newName != $oldName) {
                        $this->handleImageRename($category, $oldFilename);
                    } else {
                        $category->setImgCategory($oldFilename);
                    }
                }

                flash()
                    ->title('Exito!')
                    ->option('timeout', 3000)
                    ->success('Categoria Editado con exito!' );

                parent::updateEntity($em, $category);
            }catch(\Exception $e){
                flash()
                    ->option('timeout', 3000)
                    ->error('Error al editar la Categoria' );
                throw new \RuntimeException($e->getMessage());
            }
        }
    }

    public function handleImageRename($category, $filename): void
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $newFilename = sprintf('%d_%s.%s',
            $category->getId(),
            $category->getName(),
            $extension
        );  

        $oldFilePath = $this->categoryDirectory . '/' . $category->getId() . '/' . 'CategoryProfileImage' . '/' . $filename;
        $newFilePath = $this->categoryDirectory . '/' . $category->getId() . '/' . 'CategoryProfileImage' . '/' . $newFilename;

        if (!file_exists($oldFilePath)) {
            flash()
                ->option('timeout', 3000)
                ->info('El archivo antiguo no existe.');
        }

        try {
            $category->setImgCategory($newFilename);

            rename($oldFilePath, $newFilePath);
        } catch (\Exception $e) {
            flash()
                ->option('timeout', 3000)
                ->error('Error al renombrar el archivo: ' . $e->getMessage());
        }
    }

    public function deleteEntity(EntityManagerInterface $em, $category): void
    {
        if ($category instanceof Category) {
            $genericCategory = $em->getRepository(Category::class)->findOneBy(['name' => 'Sin Especificar']);

            if ($genericCategory) {
                foreach ($category->getProducts() as $product) {
                    $categoryId = $product->getCategory()->getId();
                    $filename = $product->getImgProduct();

                    $oldFilePath = $this->categoryDirectory . '/' . $categoryId . '/' . $filename;
                    
                    $product->setCategory($genericCategory);

                    $categoryId = $product->getCategory()->getId();

                    $newFilePath = $this->categoryDirectory . '/' . $categoryId . '/' . $filename;
                    
                    rename($oldFilePath, $newFilePath);
                }
            }
        }

        try {
            $this->handleImageDelete($category);
            $this->deleteCategoryDirectory($category, $this->categoryDirectory);
            flash()
                ->title('Exito!')
                ->option('timeout', 3000)
                ->success('Categoria Eliminada Correctamente.');
            parent::deleteEntity($em, $category);
        } catch(\Exception $e){
            flash()
                ->option('timeout', 3000)
                ->error('Debes los productos dentro de:' . ' ' .$category->getName());
        }
    }

    private function deleteCategoryDirectory($category, string $baseDirectory): string
    {
        $categoryDirectory = $baseDirectory . '/' . $category->getId();
        $this->deleteCategoryImageDirectory($category, $categoryDirectory);
        if (is_dir($categoryDirectory)) {
            rmdir($categoryDirectory);
        }
        
        return $categoryDirectory;
    }

    private function deleteCategoryImageDirectory($category, string $baseDirectory): string
    {
        $categoryImageDirectory = $baseDirectory . '/' . 'CategoryProfileImage';
        if (is_dir($categoryImageDirectory)) {
            rmdir($categoryImageDirectory);
        }
        
        return $categoryImageDirectory;
    }

    public function handleImageDelete($category, $filename = null): void
    {
        if (!$filename) {
            $filename = $category->getImgCategory();
        }

        $categoryImageDirectory = $this->categoryDirectory . '/' . $category->getId() . '/' . 'CategoryProfileImage' . '/' . $filename;

        if (!file_exists($categoryImageDirectory)) {
            flash()
                ->title('Información')
                ->option('timeout', 3000)
                ->info('El archivo no existe en el sistema.');
        }

        unlink($categoryImageDirectory);
    }

    private function handleImageUpload(Category $category, $imgCategoryFile): void
    {
        $categoryId = $category->getId();
        $newFilename = sprintf('%d_%s.%s',
            $category->getId(),
            $category->getName(),
            $imgCategoryFile->guessExtension()
        );

        $categoryImageDirectory = $this->categoryDirectory . '/' . $categoryId . '/' . 'CategoryProfileImage' . '/' . $newFilename;

        try {
            $this->resizeAndSaveImage($imgCategoryFile, $categoryImageDirectory);
            $category->setImgCategory($newFilename);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error al subir la imagen: ' . $e->getMessage());
        }
    }

    private function resizeAndSaveImage(UploadedFile $imgCategoryFile, string $targetPath, int $quality = 75): void
    {
        // Crear la imagen original
        $originalImage = imagecreatefromstring(file_get_contents($imgCategoryFile->getPathname()));
        list($originalWidth, $originalHeight) = getimagesize($imgCategoryFile->getPathname());

        // Establecer dimensiones de salida (1920 x 1080)
        $outputWidth = 1920;
        $outputHeight = 1080;

        // Crear una nueva imagen con las dimensiones especificadas
        $newImage = imagecreatetruecolor($outputWidth, $outputHeight);

        // Redimensionar la imagen original
        imagecopyresampled(
            $newImage, 
            $originalImage, 
            0, 0, 
            0, 0, 
            $outputWidth, $outputHeight, 
            $originalWidth, $originalHeight
        );

        // Guardar la imagen con la calidad especificada para comprimir
        imagejpeg($newImage, $targetPath, $quality);

        // Liberar memoria
        imagedestroy($originalImage);
        imagedestroy($newImage);
    }
}
