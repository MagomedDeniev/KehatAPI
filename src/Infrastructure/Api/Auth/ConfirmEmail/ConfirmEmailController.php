<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\ConfirmEmail;

use App\Application\Auth\ConfirmEmail\ConfirmEmailCommand;
use App\Application\Auth\ConfirmEmail\ConfirmEmailHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class ConfirmEmailController extends AbstractController
{
    #[Route('/api/confirm/email', name: 'api_confirm_email', methods: ['POST'])]
    public function confirmEmail(#[MapRequestPayload] ConfirmEmailRequest $confirmEmailRequest, ConfirmEmailHandler $handler): JsonResponse
    {
        $result = $handler(new ConfirmEmailCommand(
            token: $confirmEmailRequest->token
        ));

        return $this->json([
            'success' => true,
            'data' => [
                'email' => $result->email,
            ],
            'message' => $result->message,
        ], 201);
    }
}
