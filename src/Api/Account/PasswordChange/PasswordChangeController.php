<?php

declare(strict_types=1);

namespace App\Api\Account\PasswordChange;

use App\Doctrine\Entity\User;
use App\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class PasswordChangeController extends AbstractController
{
    #[Route('/api/account/password', name: 'api_account_password_change', methods: ['PATCH'])]
    public function passwordChange(#[CurrentUser] ?User $user, #[MapRequestPayload] PasswordChangeRequest $changePasswordRequest, PasswordChangeHandler $handler, JsonResponder $responder): JsonResponse
    {
        $result = $handler(new PasswordChangeCommand(
            userId: $user?->getId() ?? throw $this->createAccessDeniedException('User is not authenticated.'),
            currentPassword: $changePasswordRequest->currentPassword,
            newPassword: $changePasswordRequest->newPassword,
        ));

        return $responder->success(
            message: $result->message
        );
    }
}
