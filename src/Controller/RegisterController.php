<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Form\UserType;
use Cocur\Slugify\Slugify;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class RegisterController extends AbstractController
{
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        UserRepository $userRepository
    ) {
        $this->em = $em;
        $this->slugger = $slugger;
        $this->userRepository = $userRepository;
    }

    #[Route('/register', name: 'user_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, Security $security): Response
    {
        if ($security->getUser()) {
            return $this->redirectToRoute('home');
        }
        
        $user = new User();

        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => false
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $this->saveUser(
                $form,
                $user,
                $passwordHasher,
                $this->getParameter('kernel.project_dir') . '/public/uploads/images/profiles',
                $this->slugger
            );
            flash()
                ->title('Exito!')
                ->option('timeout', 3000)
                ->success('Usuario registrado correctamente');
                
            return $this->redirectToRoute('user_login');
		}

        return $this->render('register/index.html.twig', [
            'title' => 'Registrarme',
            'form' => $form->createView(),
        ]);
    }

    public function saveUser(
        $form,
        User $user,
        UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%kernel.project_dir%/public/uploads/images/profiles')] string $imagesDirectory
    ) {
        
        $data = $form->getData();

        $plaintextPassword = $data->getPassword();
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);

        $fullname = $data->getName() . ' ' . $data->getLastname();

        $user->setName($data->getName());
        $this->generateUniqueSlug($user);
        $user->setLastname($data->getLastname());
        $user->setEmail($data->getEmail());
        $user->setGender($data->getGender());

        if (!$user->getId()) {
            $this->em->persist($user);
            $this->em->flush();
        }

        $userDirectory = $this->createUserDirectory($user, $imagesDirectory);

        $imageFile = $form->get('img_profile')->getData();
        if ($imageFile) {
            
            $newFilename = sprintf('%d_%s_%s.%s',
                $user->getId(),
                $user->getName(),
                $user->getLastname(),
                $imageFile->guessExtension()
            );

            try {
                $userImageDirectory = $imagesDirectory . '/' . $user->getId();
                $this->resizeAndSaveImage($imageFile, $userImageDirectory . '/' . $newFilename);
                $user->setImgProfile($newFilename);
                $changes = true;
            } catch (FileException $e) {
               // Manejo de excepciones durante la carga del archivo
                flash()
                    ->option('timeout', 3000)
                    ->error('Error al subir la imagen.');
                return $this->redirect($this->generateUrl('user_edit', [
                    'id' => $user->getId()
                ]));
            }
        }

        $this->em->persist($user);
        $this->em->flush();
    }

    private function createUserDirectory(User $user, string $baseDirectory): string
    {
        $userDirectory = $baseDirectory . '/' . $user->getId();

        if (!is_dir($userDirectory)) {
            mkdir($userDirectory, 0777, true);
        }

        return $userDirectory;
    }

    public function generateUniqueSlug(User $user): void
    {
        $slugify = new Slugify();
        $fullName = $user->getFullName();

        $slug = $slugify->slugify($fullName);
        $originalSlug = $slug;

        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $user->setSlug($slug);
    }

    private function slugExists(string $slug): bool
    {
        return $this->userRepository->findOneBy(['slug' => $slug]) !== null;
    }

    private function resizeAndSaveImage(UploadedFile $imageFile, string $targetPath, int $size = 300): void
    {
        // Cargar la imagen original
        $originalImage = imagecreatefromstring(file_get_contents($imageFile->getPathname()));
        list($originalWidth, $originalHeight) = getimagesize($imageFile->getPathname());

        // Calcular el tamaño del recorte
        $cropSize = min($originalWidth, $originalHeight);

        // Crear una nueva imagen cuadrada
        $newImage = imagecreatetruecolor($size, $size);

        // Calcular las coordenadas del recorte
        $xOffset = ($originalWidth - $cropSize) / 2;
        $yOffset = ($originalHeight - $cropSize) / 2;

        // Redimensionar y recortar la imagen
        imagecopyresampled($newImage, $originalImage, 0, 0, $xOffset, $yOffset, $size, $size, $cropSize, $cropSize);

        // Guardar la imagen redimensionada en el disco
        imagejpeg($newImage, $targetPath, 100); // Guardar como JPEG

        // Liberar memoria
        imagedestroy($originalImage);
        imagedestroy($newImage);
    }
}
