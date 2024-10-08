<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $em;
    private $slugger;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%kernel.project_dir%/public/uploads/images/profiles')] string $imagesDirectory,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->imagesDirectory = $imagesDirectory;
        $this->em = $em;
        $this->slugger = $slugger;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Usuario')
            ->setEntityLabelInPlural('Usuarios')
            ->setSearchFields(['name', 'lastname', 'email'])
            ->setDefaultSort(['id' => 'DESC']);
    }
    
    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->onlyOnIndex(),
            AvatarField::new('AvatarUrl','Avatar')->hideOnForm(),
            TextField::new('fullname', 'Nombre')->onlyOnIndex(),
            TextField::new('name', 'Nombre')
                ->setRequired(true)
                ->onlyOnForms(),
            TextField::new('lastname', 'Apellido')
                ->onlyOnForms(),
            EmailField::new('email', 'Email')->setRequired(true),
        ];

        if (Crud::PAGE_NEW === $pageName) {
            $fields[] = 
                TextField::new('password', 'Contraseña')
                    ->setFormType(PasswordType::class)
                    ->setRequired(true)
                    ->setFormTypeOption('validation_groups', ['registration']);
            $fields[] = 
                TextField::new('img_profile', 'Imagen')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'required' => false,
                ])
                ->setHelp('Sube una imagen para el perfil.');
        }

        $fields[] = 
            ChoiceField::new('gender', 'Genero')
                ->setChoices([
                    'Hombre' => 'male',
                    'Mujer' => 'female',
                    'Otro' => 'other',
                ])->setRequired(false);

        $fields[] = 
            ChoiceField::new('roles', 'Rol')
                ->setChoices([
                    'Administrador' => 'ROLE_ADMIN',
                    'Usuario' => 'ROLE_USER',
                    'Moderador' => 'ROLE_MODERATOR',
                ])->allowMultipleChoices()
                ->setHelp('Roles Disponibles: ROLE_ADMIN, ROLE_MODERATOR, ROLE_USER')
                ->setRequired(false);;

        return $fields;
    }

    public function createEntity(string $entityFqcn)
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $user->setCreatedAt(new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires')));

        return $user;
    }

    public function persistEntity(EntityManagerInterface $em, $user): void
    {
        if ($user instanceof User) {
            $this->sanitizeUser($user);
            
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            );
            $user->setPassword($hashedPassword);


            if (empty($user->getRoles())) {
                $user->setRoles(['ROLE_USER']);
            }

            $imgProfileFile = $this->getContext()->getRequest()->files->get('User')['img_profile'];
            
            try {
                $em->persist($user);
                $em->flush();
                
                $userDirectory = $this->createUserDirectory($user);

                if ($imgProfileFile) {
                    $this->handleImageUpload($user, $imgProfileFile);
                }

                flash()
                    ->title('Exito!')
                    ->option('timeout', 3000)
                    ->success('Usuario creado!' );

                parent::persistEntity($em, $user);
            } catch(\Exception $e){
                flash()
                    ->option('timeout', 3000)
                    ->error('Error al guardar el usuario' );

                $em->remove($user);
                $em->flush();
            }
        }
    }

    public function updateEntity(EntityManagerInterface $em, $user): void
    {
        if ($user instanceof User) {
            $this->sanitizeUser($user);

            $originalData = $em->getUnitOfWork()->getOriginalEntityData($user);

            $oldName = $originalData['name'];
            $oldLastname = $originalData['lastname'];
            $newName = $user->getName();
            $newLastname = $user->getLastname();
            $filename = $user->getImgProfile();

            try {
                $em->persist($user);
                $em->flush();

                if ($newName != $oldName || $newLastname != $oldLastname) {
                    if ($filename) {
                        $this->handleImageRename($user);
                    }
                }

                flash()
                    ->title('Exito!')
                    ->option('timeout', 3000)
                    ->success('Usuario Editado con exito!' );

                parent::updateEntity($em, $user);
            }catch(\Exception $e){
                flash()
                    ->option('timeout', 3000)
                    ->error('Error al editar el usuario' );
            }
        }
    }

    public function handleImageRename($user): void
    {
        $filename = $user->getImgProfile();
        $uniqueId = uniqid();
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $newFilename = sprintf('%d_%s_%s_%s.%s',
            $user->getId(),
            $user->getName(),
            $user->getLastname(),
            $uniqueId,
            $extension
        );  

        $oldFilePath = $this->getUserDirectory($user) . '/' . $filename;
        $newFilePath =  $this->getUserDirectory($user) . '/' . $newFilename;

        if (!file_exists($oldFilePath)) {
            flash()
                ->option('timeout', 3000)
                ->info('El archivo antiguo no existe.');
        }

        try {
            $user->setImgProfile($newFilename);

            rename($oldFilePath, $newFilePath);
        } catch (\Exception $e) {
            flash()
                ->option('timeout', 3000)
                ->error('Error al renombrar el archivo: ' . $e->getMessage());
        }
    }
    

    public function sanitizeUser(User $user): void
    {
        $name = strip_tags($user->getName());
        $user->setName($name);
    
        if ($lastname = $user->getLastname()) {
            $user->setLastname(strip_tags($lastname));
        }

        if ($email = $user->getEmail()) {
            $user->setEmail(filter_var(strip_tags($email), FILTER_SANITIZE_EMAIL));
        }
    }

    private function handleImageUpload(User $user, $imgProfileFile): void
    {
        $newFilename = sprintf('%d_%s_%s_%s.%s',
            $user->getId(),
            $user->getName(),
            $user->getLastname(),
            uniqid(),
            $imgProfileFile->guessExtension()
        );

        $userImageDirectory = $this->getUserDirectory($user) . '/' . $newFilename;
        
        try {
            $this->resizeAndSaveImage($imgProfileFile, $userImageDirectory);
            $user->setImgProfile($newFilename);

        } catch (\Exception $e) {
            throw new \RuntimeException('Error al subir la imagen: ' . $e->getMessage());
        }
    }

    private function createUserDirectory($user): string
    {
        $userDirectory = $this->getUserDirectory($user);

        if (!is_dir($userDirectory)) {
            mkdir($userDirectory, 0777, true);
        }

        return $userDirectory;
    }

    private function resizeAndSaveImage(UploadedFile $imgProfileFile, string $targetPath, int $size = 300): void
    {
        $originalImage = imagecreatefromstring(file_get_contents($imgProfileFile->getPathname()));
        list($originalWidth, $originalHeight) = getimagesize($imgProfileFile->getPathname());

        $cropSize = min($originalWidth, $originalHeight);

        $newImage = imagecreatetruecolor($size, $size);

        $xOffset = ($originalWidth - $cropSize) / 2;
        $yOffset = ($originalHeight - $cropSize) / 2;

        imagecopyresampled($newImage, $originalImage, 0, 0, $xOffset, $yOffset, $size, $size, $cropSize, $cropSize);

        imagejpeg($newImage, $targetPath, 100);

        imagedestroy($originalImage);
        imagedestroy($newImage);
    }

    public function handleImageDelete($user): void
    {
        $filename = $user->getImgProfile();

        $userImageDirectory = $this->getUserDirectory($user) . '/' . $filename;

        if ($filename) {
            if (file_exists($userImageDirectory)) {
                unlink($userImageDirectory);
            }
        }
    }

    private function handleDirectoryDelete($user)
    {
        $userDirectory = $this->getUserDirectory($user);

        if (is_dir($userDirectory)) {
            rmdir($userDirectory);
        }
    }

    public function getUserDirectory($user)
    {
        return $this->imagesDirectory . '/' . $user->getId();
    }

    public function deleteEntity(EntityManagerInterface $em, $user): void
    {
        try {
            $this->handleImageDelete($user);
            $this->handleDirectoryDelete($user);

            flash()
                ->title('Exito!')
                ->option('timeout', 3000)
                ->success('Usuario Eliminado Correctamente.');

            parent::deleteEntity($em, $user);
        } catch (Exception $e) {
            flash()
                ->title('Exito!')
                ->option('timeout', 3000)
                ->success('Error al eliminar el archivo.');
        }

    }
}