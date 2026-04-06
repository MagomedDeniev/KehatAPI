<?php

namespace App\Infrastructure\Api\Profile\ChangeMyPassword;

use App\Application\Profile\ChangeMyPassword\ChangeMyPasswordCommand;
use App\Application\Profile\ChangeMyPassword\ChangeMyPasswordHandler;
use App\Infrastructure\Doctrine\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ChangeMyPasswordController extends AbstractController
{
    #[Route('/api/me/password', name: 'api_change_password', methods: ['PATCH'])]
    public function changePassword(#[CurrentUser] ?User $user, #[MapRequestPayload] ChangeMyPasswordRequest $changePasswordRequest, ChangeMyPasswordHandler $handler): JsonResponse {
        if ($user === null || $user->getId() === null) {
            throw $this->createAccessDeniedException('User is not authenticated or not have id.');
        }

        $result = $handler(new ChangeMyPasswordCommand(
            userId: $user->getId(),
            password: $changePasswordRequest->newPassword
        ));

        return $this->json([
            'success' => true,
            'data' => [],
            'message' => $result->message,
        ], 201);
    }
}
