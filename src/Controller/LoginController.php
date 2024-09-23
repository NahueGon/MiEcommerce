<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'user_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $user = $this->getUser();
        if ($error) {
            flash()
                ->option('position', 'bottom-right')
                ->option('timeout', 3000)
                ->error('Credenciales incorrectas, por favor intenta nuevamente.');
        }
        if($user){
            flash()
                ->title('Exito!')
                ->option('position', 'bottom-right')
                ->option('timeout', 3000)
                ->success('Inicio de sesion exitoso.');
            return $this->redirect($this->generateUrl('home'));
        }
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('login/login.html.twig', [
            'user' => $user,
            'username' => $lastUsername,
            'title' => 'Iniciar Sesión'
        ]);
    }

    #[Route(path: '/logout', name: 'user_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
