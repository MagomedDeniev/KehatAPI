<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\RestorePassword;

use App\Application\Auth\RestorePassword\RestorePasswordCommand;
use App\Application\Auth\RestorePassword\RestorePasswordHandler;
use App\Infrastructure\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RestorePasswordController extends AbstractController
{
    #[Route('/api/restore/password', name: 'api_restore_password', methods: ['POST'])]
    public function restorePassword(#[MapRequestPayload] RestorePasswordRequest $restorePasswordRequest, RestorePasswordHandler $handler, JsonResponder $responder): JsonResponse
    {
        $result = $handler(new RestorePasswordCommand(
            token: $restorePasswordRequest->token,
            password: $restorePasswordRequest->newPassword
        ));

        return $responder->success(
            data: [
                'userId' => $result->userId,
            ],
            message: $result->message
        );
    }
}
