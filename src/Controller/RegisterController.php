<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ["POST", "GET"])]
    public function index(): Response
    {
        return $this->render('register.html.twig', [
            'controller_name' => 'RegisterController',
        ]);
    }
}