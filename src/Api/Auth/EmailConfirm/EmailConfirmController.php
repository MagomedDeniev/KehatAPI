<?php

declare(strict_types=1);

namespace App\Api\Auth\EmailConfirm;

use App\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class EmailConfirmController extends AbstractController
{
    #[Route('/api/auth/email/confirm', name: 'api_auth_email_confirm', methods: ['POST'])]
    public function emailConfirm(#[MapRequestPayload] EmailConfirmRequest $confirmEmailRequest, EmailConfirmHandler $handler, JsonResponder $responder): JsonResponse
    {
        $result = $handler(new EmailConfirmCommand(
            token: $confirmEmailRequest->token
        ));

        return $responder->success(
            message: $result->message
        );
    }
}
