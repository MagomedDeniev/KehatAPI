<?php

namespace App\Infrastructure\Api\Auth\RestorePassword;

use App\Application\Auth\RestorePassword\RestorePasswordCommand;
use App\Application\Auth\RestorePassword\RestorePasswordHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RestorePasswordController extends AbstractController
{
    #[Route('/api/restore/password', name: 'api_restore_password', methods: ['POST'])]
    public function restorePassword(#[MapRequestPayload] RestorePasswordRequest $restorePasswordRequest, RestorePasswordHandler $handler): JsonResponse {
        $result = $handler(new RestorePasswordCommand(
            token: $restorePasswordRequest->token,
            password: $restorePasswordRequest->newPassword
        ));

        return $this->json([
            'success' => true,
            'data' => [
                'userId' => $result->userId,
            ],
            'message' => $result->message,
        ], 201);
    }
}
