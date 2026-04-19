<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Account\CurrentUser;

use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CurrentUserController extends AbstractController
{
    #[Route('/api/account', name: 'api_account', methods: ['GET'])]
    public function account(#[CurrentUser] User $user, JsonResponder $responder): JsonResponse
    {
        return $responder->success(
            data: [
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'gender' => $user->getGender(),
                'birthDate' => $user->getBirthDate()->format('Y-m-d'),
                'roles' => $user->getRoles(),
                'registeredAt' => $user->getRegisteredAt()->format('Y-m-d'),
            ]
        );
    }
}
