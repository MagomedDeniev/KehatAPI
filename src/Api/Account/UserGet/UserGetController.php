<?php

declare(strict_types=1);

namespace App\Api\Account\UserGet;

use App\Doctrine\Entity\User;
use App\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class UserGetController extends AbstractController
{
    #[Route('/api/account/user', name: 'api_account_user_get', methods: ['GET'])]
    public function userGet(#[CurrentUser] User $user, JsonResponder $responder): JsonResponse
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
