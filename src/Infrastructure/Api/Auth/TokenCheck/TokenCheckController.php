<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\TokenCheck;

use App\Application\Auth\TokenCheck\TokenCheckCommand;
use App\Application\Auth\TokenCheck\TokenCheckHandler;
use App\Infrastructure\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class TokenCheckController extends AbstractController
{
    #[Route('/api/auth/token/check', name: 'api_auth_token_check', methods: ['POST'])]
    public function tokenCheck(#[MapRequestPayload] TokenCheckRequest $checkTokenRequest, TokenCheckHandler $handler, JsonResponder $responder): JsonResponse
    {
        $result = $handler(new TokenCheckCommand(
            token: $checkTokenRequest->token,
            type: $checkTokenRequest->type
        ));

        return $responder->success(
            message: $result->message
        );
    }
}
