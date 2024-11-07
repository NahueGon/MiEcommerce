<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserController extends AbstractController
{
    private $em;
    private $slugger;
    private $imagesDirectory;
    private $userRepository;

    public function __construct(
        EntityManagerInterface $em,
        SluggerInterface $slugger, 
        #[Autowire('%kernel.project_dir%/public/uploads/images/profiles')] string $imagesDirectory,
        UserRepository $userRepository
    ) {
        $this->em = $em;
        $this->slugger = $slugger;
        $this->imagesDirectory = $imagesDirectory;
        $this->userRepository = $userRepository;
    }

    private function findUserBySlug(string $slug): User
    {
        $user = $this->userRepository->findOneBySlug($slug);
        
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado');
        }
        
        return $user;
    }

    #[Route('/profile/{slug}', name: 'user_show')]
    public function show(string $slug): Response
    {
        $user = $this->findUserBySlug($slug);
        $currentUser = $this->getUser();

        if ($currentUser->getSlug() !== $slug) {
            return $this->redirectToRoute('user_show', ['slug' => $currentUser->getSlug()]);
        }

        return $this->render('user/detail.html.twig', [
            'title' => 'Mi Perfil',
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit/{slug}', name: 'user_edit')]
    public function edit(string $slug)
    {
        $user = $this->findUserBySlug($slug);
        $currentUser = $this->getUser();

        if ($currentUser->getSlug() !== $slug) {
            return $this->redirectToRoute('user_show', ['slug' => $currentUser->getSlug()]);
        }

        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true
        ]);

        return $this->render('user/edit.html.twig',[
            'title' => 'Editar',
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/profile/update/{slug}', name: 'user_update')]
    public function update(
        $slug,
        Request $request,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $user = $this->findUserBySlug($slug);
        $currentUser = $this->getUser();

        if ($currentUser->getSlug() !== $slug) {
            return $this->redirectToRoute('user_show', ['slug' => $currentUser->getSlug()]);
        }

        $oldEmail = $user->getEmail();
        $oldName = $user->getName();
        $oldLastname = $user->getLastname();
        $oldGender = $user->getGender();
        $oldImgProfile = $user->getImgProfile();
        
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $imageFile = $form->get('img_profile')->getData();

            $plaintextOldPassword = $form->get('old_password')->getData();
            $plaintextNewPassword = $form->get('new_password')->getData();

            if(!empty($plaintextOldPassword) || !empty($plaintextNewPassword)){
                if(empty($plaintextOldPassword) || empty($plaintextNewPassword)){
                    $this->addCustomFlash('error', 'Ambos campos de contraseña deben estar completos.');
                    return $this->redirectToRoute('user_edit', ['slug' => $user->getSlug()]);
                }
            
                if (!$passwordHasher->isPasswordValid($user, $plaintextOldPassword)) {
                    $this->addCustomFlash('error', 'La contraseña actual es incorrecta');
                    return $this->redirectToRoute('user_edit', ['slug' => $user->getSlug()]);
                }
            
                $hashedNewPassword = $passwordHasher->hashPassword($user, $plaintextNewPassword);
                $user->setPassword($hashedNewPassword);
            }

            $data = $form->getData();

            if ($oldEmail !== $data->getEmail()) {
                $user->setEmail($data->getEmail());
            }

            if ($imageFile) {
                $this->handleImageDelete($user, $oldImgProfile);
                $this->handleImageUpload($user, $imageFile);
            } else {
                $nameChanged = $oldName !== $data->getName();
                $lastnameChanged = $oldLastname !== $data->getLastname();
            
                if ($nameChanged || $lastnameChanged) {
                    $user->setName($data->getName());
                    $user->setLastname($data->getLastname());
            
                    if ($oldImgProfile) {
                        $this->handleImageRename($user, $oldImgProfile);
                    }
                } else {
                    $user->setImgProfile($oldImgProfile);
                }
            }

            if ($oldGender !== $data->getGender()) {
                $user->setGender($data->getGender());
            }

            $this->em->persist($user);
            $this->em->flush();
            $this->addCustomFlash('success', 'Usuario editado correctamente');

			return $this->redirect($this->generateUrl('user_show', [
				'slug' => $user->getSlug()
			]));
		}

        return $this->render('user/edit.html.twig',[
            'title' => 'Editar',
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    private function handleImageUpload(User $user, $imgProfileFile)
    {
        $newFilename = sprintf('%d_%s_%s.%s',
            $user->getId(),
            $user->getName(),
            $user->getLastname(),
            $imgProfileFile->guessExtension()
        );

        $userImageDirectory = $this->getUserDirectory($user) . '/' . $newFilename;
        
        try {
            $this->resizeAndSaveImage($imgProfileFile, $userImageDirectory);
            $user->setImgProfile($newFilename);

        } catch (FileException $e) {
            $this->addCustomFlash('error', 'Error al subir la imagen');
            return $this->redirect($this->generateUrl('user_edit', [
                'id' => $user->getId()
            ]));
        }
    }

    private function handleImageDelete($user, $filename): void
    {
        $userImageDirectory = $this->getUserDirectory($user) . '/' . $filename;

        if ($filename) {
            if (file_exists($userImageDirectory)) {
                unlink($userImageDirectory);
            }
        }
    }

    private function handleImageRename($user, $filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $newFilename = sprintf('%d_%s_%s.%s',
            $user->getId(),
            $user->getName(),
            $user->getLastname(),
            $extension
        );  

        $oldFilePath = $this->getUserDirectory($user) . '/' . $filename;
        $newFilePath =  $this->getUserDirectory($user) . '/' . $newFilename;

        if (!file_exists($oldFilePath)) {
            $this->addCustomFlash('info', 'El archivo antiguo no existe');
        }

        try {
            $user->setImgProfile($newFilename);
            rename($oldFilePath, $newFilePath);
        } catch (\Exception $e) {
            $this->addCustomFlash('error', 'Error al renombrar el archivo');
        }
    }

    public function getUserDirectory($user)
    {
        return $this->imagesDirectory . '/' . $user->getId();
    }

    private function resizeAndSaveImage(UploadedFile $imageFile, string $targetPath, int $size = 300): void
    {
        $originalImage = imagecreatefromstring(file_get_contents($imageFile->getPathname()));
        list($originalWidth, $originalHeight) = getimagesize($imageFile->getPathname());

        $cropSize = min($originalWidth, $originalHeight);

        $newImage = imagecreatetruecolor($size, $size);

        $xOffset = ($originalWidth - $cropSize) / 2;
        $yOffset = ($originalHeight - $cropSize) / 2;

        imagecopyresampled($newImage, $originalImage, 0, 0, $xOffset, $yOffset, $size, $size, $cropSize, $cropSize);

        imagejpeg($newImage, $targetPath, 100);

        imagedestroy($originalImage);
        imagedestroy($newImage);
    }

    function addCustomFlash(string $type, mixed $message): void
    {
        flash()
            ->option('position', 'bottom-right')
            ->option('timeout', 3000)
            ->$type($message);
    }

}
