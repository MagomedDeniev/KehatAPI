<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Users\Profile;

use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/api/users/profile/{username:user}', name: 'api_users_user_profile', methods: ['GET'])]
    public function userProfile(User $user, JsonResponder $responder): JsonResponse
    {
        return $responder->success(
            data: [
                'username' => $user->getUsername(),
                'registeredAt' => $user->getRegisteredAt()->format('Y-m-d'),
            ]
        );
    }
}
