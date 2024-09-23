<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

class UserCrudController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('admin/users', name: 'users')]
    public function index(): Response
    {
        $users = $this->em->getRepository(User::class)->findAll();

        return $this->render('user_crud/index.html.twig', [
            'title' => 'Usuarios',
            'users' => $users,
        ]);
    }
}
