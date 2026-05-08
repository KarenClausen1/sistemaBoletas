<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {
        // Si no hay usuario en sesión, redirigir al login
        $sessionUser = $request->getSession()->get('usuario');
        if (empty($sessionUser)) {
            return $this->redirectToRoute('app_login', ['redirect' => '/boletas']);
        }

        // Si está autenticado, redirigir al listado de boletas
        return $this->redirectToRoute('boleta_index');
    }
}