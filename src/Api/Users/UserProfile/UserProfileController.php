<?php

declare(strict_types=1);

namespace App\Api\Users\UserProfile;

use App\Doctrine\Entity\User;
use App\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class UserProfileController extends AbstractController
{
    #[Route('/api/users/{username:user}/profile', name: 'api_users_user_profile', methods: ['GET'])]
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
