<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());

        // return $this->render('admin/dashboard.html.twig', [
        //     'some_variable' => 'value',
        // ]);
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        if (!$user instanceof User) {
            throw new \Exception('Wrong user');
        }

        return parent::configureUserMenu($user)
            ->setAvatarUrl($user->getAvatarUrl())
            ->setName($user->getName() . ' ' . $user->getLastname())
            ->addMenuItems([
                MenuItem::linkToUrl('Mi Perfil', 'fas fa-user', $this->generateUrl('user_show', [
                    'id' => $user->getId()
                ])),
                MenuItem::linkToUrl('Sitio Web', 'fas fa-home', $this->generateUrl('home'))
            ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('MiEcommerce');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::section('Usuarios'),
            MenuItem::linkToCrud('Usuarios', 'fa fa-users', User::class),
            MenuItem::section('Productos'),
            MenuItem::linkToCrud('Productos', 'fa fa-shopping-cart', Product::class),
            MenuItem::linkToCrud('Categorias', 'fa fa-th-list', Category::class),
        ];
    }

    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
