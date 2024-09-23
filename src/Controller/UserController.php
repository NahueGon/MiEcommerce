<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\UserType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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
    public function update(User $user, Request $request, UserPasswordHasherInterface $passwordHasher)
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
            $changes = false;

            if ($oldEmail !== $data->getEmail()) {
                $user->setEmail($data->getEmail());

                $this->em->persist($user);
                $this->em->flush();

                flash()
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

}
