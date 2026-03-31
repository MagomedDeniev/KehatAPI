<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profile', name: 'profile_')]
final class ProfileController extends AbstractController
{
    #[Route('/{username}', name: 'index')]
    public function index(User $user): Response
    {
        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{username}/edit', name: 'edit')]
    public function edit(User $user): Response
    {
        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
        ]);
    }
}
