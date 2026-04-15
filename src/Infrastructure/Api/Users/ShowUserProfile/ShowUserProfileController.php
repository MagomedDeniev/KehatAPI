<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Users\ShowUserProfile;

use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Service\JsonResponder;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ShowUserProfileController extends AbstractController
{
    #[Route('/api/users/profile/{username:user}', name: 'api_user_profile', methods: ['GET'])]
    public function profile(User $user, JsonResponder $responder): JsonResponse
    {
        return $responder->success(
            data: [
                'username' => $user->getUsername(),
                'registeredAt' => $user->getRegisteredAt()->format('Y-m-d'),
            ]
        );
    }
}
