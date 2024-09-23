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

class RegisterController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/register', name: 'user_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => false
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $this->saveUser($form, $user, $passwordHasher);

            flash()
                ->title('Exito!')
                ->option('position', 'bottom-right')
                ->option('timeout', 3000)
                ->success('Usuario registrado correctamente');
                
            return $this->redirect($this->generateUrl('user_login'));
		}

        return $this->render('register/index.html.twig', [
            'title' => 'Registrar',
            'form' => $form->createView(),
        ]);
    }

    public function saveUser($form, User $user, UserPasswordHasherInterface $passwordHasher)
    {
        $data = $form->getData();

        $plaintextPassword = $data->getPassword();
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);

        $user->setName($data->getName());
        $user->setLastname($data->getLastname());
        $user->setEmail($data->getEmail());
        $user->setGender($data->getGender());
        
        $this->em->persist($user);
        $this->em->flush();
    }
}
