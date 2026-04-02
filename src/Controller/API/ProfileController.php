<?php

namespace App\Controller\API;

use App\DTO\Request\ChangeMeRequest;
use App\DTO\Request\ChangePasswordRequest;
use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_profile_')]
final class ProfileController extends AbstractController
{
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'registeredAt' => $user->getRegisteredAt()?->format(DATE_ATOM),
        ]);
    }

    #[Route('/me', name: 'change_me', methods: ['PATCH'])]
    public function changeMe(#[CurrentUser] User $user, #[MapRequestPayload] ChangeMeRequest $changeMeRequest, UserService $userService): JsonResponse
    {
        $userService->updateProfile($user, $changeMeRequest);

        return $this->json([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);
    }

    #[Route('/me/password', name: 'change_password', methods: ['PATCH'])]
    public function changePassword(#[CurrentUser] ?User $user, #[MapRequestPayload] ChangePasswordRequest $changePasswordRequest, UserService $userService): JsonResponse {
        $userService->updatePasswordFromUser($user, $changePasswordRequest->newPassword);

        return $this->json([
            'success' => true,
            'message' => 'Password updated successfully',
        ]);
    }
}
