<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\CheckToken;

use App\Application\Auth\CheckToken\CheckTokenCommand;
use App\Application\Auth\CheckToken\CheckTokenHandler;
use App\Infrastructure\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CheckTokenController extends AbstractController
{
    #[Route('/api/auth/check/token', name: 'api_check_token', methods: ['POST'])]
    public function checkToken(#[MapRequestPayload] CheckTokenRequest $checkTokenRequest, CheckTokenHandler $handler, JsonResponder $responder): JsonResponse
    {
        $result = $handler(new CheckTokenCommand(
            token: $checkTokenRequest->token,
            type: $checkTokenRequest->type
        ));

        return $responder->success(
            message: $result->message
        );
    }
}
