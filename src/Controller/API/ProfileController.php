<?php

namespace App\Controller\API;

use App\DTO\Request\ChangeMeRequest;
use App\DTO\Request\ChangePasswordRequest;
use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function changeMe(#[CurrentUser] User $user, Request $request, ValidatorInterface $validator, UserService $userService): JsonResponse
    {
        $data = $request->toArray();

        $dto = new ChangeMeRequest();
        $dto->username = $data['username'] ?? '';
        $dto->email = $data['email'] ?? '';

        $errors = [];

        foreach ($validator->validate($dto) as $error) {
            $errors[$error->getPropertyPath()][] = $error->getMessage();
        }

        if ($errors) {
            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }

        $userService->updateProfile($user, $dto);

        return $this->json([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);
    }

    #[Route('/me/password', name: 'change_password', methods: ['PATCH'])]
    public function changePassword(#[CurrentUser] ?User $user, Request $request, ValidatorInterface $validator, UserService $userService): JsonResponse {
        $data = $request->toArray();

        $dto = new ChangePasswordRequest();
        $dto->currentPassword = $data['currentPassword'] ?? '';
        $dto->newPassword = $data['newPassword'] ?? '';
        $dto->repeatPassword = $data['repeatPassword'] ?? '';

        $errors = [];

        foreach ($validator->validate($dto) as $error) {
            $errors[$error->getPropertyPath()][] = $error->getMessage();
        }

        if ($dto->newPassword !== $dto->repeatPassword) {
            $errors['repeatPassword'][] = 'Passwords do not match.';
        }

        if (!$userService->isPasswordValid($user, $dto->currentPassword)) {
            $errors['currentPassword'][] = 'Current password is invalid.';
        }

        if ($errors) {
            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }

        $userService->updatePassword($user, $dto->newPassword);

        return $this->json([
            'success' => true,
            'message' => 'Password updated successfully',
        ]);
    }
}
