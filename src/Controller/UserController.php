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

        return $this->render('user/detail.html.twig', [
            'title' => 'Mi Perfil',
            'user' => $user,
        ]);
    }

    #[Route('/user/edit/{id}', name: 'user_edit')]
    public function edit(User $user)
    {
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true
        ]);
        return $this->render('user/edit.html.twig',[
            'title' => 'Editar',
            'form' => $form->createView(),
            'user' => $user,
        ]);

    }

    #[Route('/user/update/{id}', name: 'user_update')]
    public function update(User $user, Request $request, UserPasswordHasherInterface $passwordHasher,  #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imagesDirectory)
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

            $imageFile = $form->get('img_profile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                try {
                    $this->resizeAndSaveImage($imageFile, $imagesDirectory . '/' . $newFilename);
                    $user->setImgProfile($newFilename);
                    $changes = true;
                } catch (FileException $e) {
                   // Manejo de excepciones durante la carga del archivo
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
            'user' => $user
        ]);
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
