<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Account\ShowMyProfile;

use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_profile_')]
final class ShowMyProfileController extends AbstractController
{
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user, JsonResponder $responder): JsonResponse
    {
        return $responder->success(
            data: [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'registeredAt' => $user->getRegisteredAt()->format(DATE_ATOM),
            ]
        );
    }
}
