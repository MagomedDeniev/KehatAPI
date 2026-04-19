<?php

declare(strict_types=1);

namespace App\Api\Auth\PasswordRestore;

use App\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PasswordRestoreController extends AbstractController
{
    #[Route('/api/auth/password/restore', name: 'api_auth_password_restore', methods: ['POST'])]
    public function passwordRestore(#[MapRequestPayload] PasswordRestoreRequest $restorePasswordRequest, PasswordRestoreHandler $handler, JsonResponder $responder): JsonResponse
    {
        $result = $handler(new PasswordRestoreCommand(
            token: $restorePasswordRequest->token,
            password: $restorePasswordRequest->password
        ));

        return $responder->success(
            data: [
                'userId' => $result->userId,
            ],
            message: $result->message
        );
    }
}
