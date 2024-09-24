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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class RegisterController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em, SluggerInterface $slugger)
    {
        $this->em = $em;
        $this->slugger = $slugger;
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
            $this->saveUser(
                $form,
                $user,
                $passwordHasher,
                $this->getParameter('kernel.project_dir') . '/public/uploads/images',
                $this->slugger
            );
            flash()
                ->title('Exito!')
                ->option('timeout', 3000)
                ->success('Usuario registrado correctamente');
                
            return $this->redirectToRoute('user_login');
		}

        return $this->render('register/index.html.twig', [
            'title' => 'Registrar',
            'form' => $form->createView(),
        ]);
    }

    public function saveUser($form, User $user, UserPasswordHasherInterface $passwordHasher, #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imagesDirectory)
    {
        $imageFile = $form->get('img_profile')->getData();
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
            try {
                $imageFile->move($imagesDirectory, $newFilename);
            } catch (FileException $e) {
                // ... handle exception if something happens during file upload
            }
            $user->setImgProfile($newFilename);
    
        }
        
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
