<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends AbstractController
{
    #[Route('/error-404', name: 'error_404')]
    public function error404(): Response
    {
        return $this->render('error/error_404.html.twig', [
            'title' => 'Error 404 - Página no encontrada',
        ]);
    }

    public function showAction(\Throwable $exception): Response // Cambia el tipo a Throwable
    {
        if ($exception instanceof NotFoundHttpException) {
            return $this->redirectToRoute('error_404');
        }

        // Manejo de otros tipos de excepciones si es necesario
        return new Response('Un error ocurrió: ' . $exception->getMessage(), 500);
    }
}