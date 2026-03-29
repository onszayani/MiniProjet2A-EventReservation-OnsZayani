<?php

namespace App\Controller;
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthPageController extends AbstractController
{
    #[Route('/login', name: 'auth_login')]
    public function login(): Response
    {
        return $this->render('auth/login.html.twig');
    }
}
