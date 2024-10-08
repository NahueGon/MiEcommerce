<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Form\UserType;
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

    public function __construct(EntityManagerInterface $em, SluggerInterface $slugger)
    {
        $this->em = $em;
        $this->slugger = $slugger;
    }

    #[Route('/user/show/{id}', name: 'user_show')]
    public function show(User $user): Response
    {
        $userImageDirectory = $user->getId() . '/' . $user->getImgProfile();

        return $this->render('user/detail.html.twig', [
            'title' => 'Mi Perfil',
            'user' => $user,
            'userImageDirectory' => $userImageDirectory,
        ]);
    }

    #[Route('/user/edit/{id}', name: 'user_edit')]
    public function edit(User $user)
    {
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true
        ]);

        $userImageDirectory = $user->getId() . '/' . $user->getImgProfile();

        return $this->render('user/edit.html.twig',[
            'title' => 'Editar',
            'form' => $form->createView(),
            'user' => $user,
            'userImageDirectory' => $userImageDirectory,
        ]);

    }

    #[Route('/user/update/{id}', name: 'user_update')]
    public function update(User $user, Request $request, UserPasswordHasherInterface $passwordHasher,  #[Autowire('%kernel.project_dir%/public/uploads/images/profiles')] string $imagesDirectory)
    {
        $oldEmail = $user->getEmail();
        $oldName = $user->getName();
        $oldLastname = $user->getLastname();
        $oldGender = $user->getGender();
        
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){

            $changes = false;
            $userDirectory = $this->editUserDirectory($user, $imagesDirectory);
            $imageFile = $form->get('img_profile')->getData();

            if ($imageFile) {
                // $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $user->getId() .'_'. $user->getName() .'_'. $user->getLastname() .'_'.uniqid().'.'.$imageFile->guessExtension();
                try {
                    $userImageDirectory = $imagesDirectory . '/' . $user->getId();
                    $this->resizeAndSaveImage($imageFile, $userImageDirectory . '/' . $newFilename);
                    $user->setImgProfile($newFilename);
                    $changes = true;
                } catch (FileException $e) {
                    flash()
                        ->option('position', 'bottom-right')
                        ->option('timeout', 3000)
                        ->error('Error al subir la imagen.');
                    return $this->redirect($this->generateUrl('user_edit', [
                        'id' => $user->getId()
                    ]));
                }
            }

            $plaintextOldPassword = $form->get('old_password')->getData();
            $plaintextNewPassword = $form->get('new_password')->getData();

            if(empty($plaintextOldPassword) && !empty($plaintextNewPassword)){
                flash()
                    ->option('position', 'bottom-right')
                    ->option('timeout', 3000)
                    ->error('Necesitas completar el campo Contraseña Actual.');
                return $this->redirect($this->generateUrl('user_edit', [
                    'id' => $user->getId()
                ]));
            }

            if(!empty($plaintextOldPassword) && empty($plaintextNewPassword)){
                flash()
                    ->option('position', 'bottom-right')
                    ->option('timeout', 3000)
                    ->error('Necesitas completar el campo Contraseña Nueva.');
                return $this->redirect($this->generateUrl('user_edit', [
                    'id' => $user->getId()
                ]));
            }

            if (!empty($plaintextOldPassword) && !empty($plaintextNewPassword)) {

                $isValidPassword = $passwordHasher->isPasswordValid($user, $plaintextOldPassword);
                
                if (!$isValidPassword)
                {
                    flash()
                        ->option('position', 'bottom-right')
                        ->option('timeout', 3000)
                        ->error('La contraseña actual es incorrecta.');

                    return $this->redirect($this->generateUrl('user_edit', [
                        'id' => $user->getId()
                    ]));
                }

                $hashedNewPassword = $passwordHasher->hashPassword($user, $plaintextNewPassword);
                $user->setPassword($hashedNewPassword);
            }

            
            $data = $form->getData();

            if ($oldEmail !== $data->getEmail()) {
                $user->setEmail($data->getEmail());

                $this->em->persist($user);
                $this->em->flush();

                flash()
                    ->title('Exito!')
                    ->option('position', 'bottom-right')
                    ->option('timeout', 3000)
                    ->success('Email actualizado');
            }

            if ($oldName !== $data->getName()) {
                $user->setName($data->getName());
                $changes = true;
            }

            if ($oldLastname !== $data->getLastname()) {
                $user->setLastname($data->getLastname());
                $changes = true;
            }

            if ($oldGender !== $data->getGender()) {
                $user->setGender($data->getGender());
                $changes = true;
            }

            if ($changes) {
                $this->em->persist($user);
                $this->em->flush();
                flash()
                    ->title('Exito!')
                    ->option('position', 'bottom-right')
                    ->option('timeout', 3000)
                    ->success('Usuario editado correctamente.');
            }

			return $this->redirect($this->generateUrl('user_show', [
				'id' => $user->getId()
			]));
		}

        return $this->render('user/edit.html.twig',[
            'title' => 'Editar',
            'form' => $form->createView(),
            'user' => $user,
            'userImageDirectory' => $userImageDirectory,
        ]);
    }

    private function editUserDirectory(User $user, string $baseDirectory): string
    {
        $userDirectory = $baseDirectory . '/' . $user->getId();
        // dd($userDirectory );
        if (!is_dir($userDirectory)) {
            mkdir($userDirectory, 0777, true);
        }

        return $userDirectory;
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

}
