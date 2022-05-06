<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController {

    #[Route("/", name: "home", methods: ['GET'])]
    public function index(): Response {

        return $this->render('home.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}