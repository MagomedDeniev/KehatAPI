<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\ForgotPassword;

use App\Application\Auth\ForgotPassword\ForgotPasswordCommand;
use App\Application\Auth\ForgotPassword\ForgotPasswordHandler;
use App\Infrastructure\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class ForgotPasswordController extends AbstractController
{
    // Тест rate-limiter
    public function __construct(
        private readonly RateLimiterFactory $forgotPasswordIpLimiter,
        private readonly RateLimiterFactory $forgotPasswordEmailLimiter,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/api/forgot/password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request, #[MapRequestPayload] ForgotPasswordRequest $forgotPasswordRequest, ForgotPasswordHandler $handler, JsonResponder $responder): JsonResponse
    {
        // Тест rate-limiter -- START -- | Создан файл rate-limiter.yaml и в services.yaml добавлены аргументы
        $ipLimit = $this->forgotPasswordIpLimiter->create($request->getClientIp() ?? 'unknown')->consume();

        if (!$ipLimit->isAccepted()) {
            return $responder->error(
                code: 'rate_limit_exceeded',
                message: 'Too many requests. Please try again later.',
                status: 429,
            );
        }

        $normalizedEmail = mb_strtolower(trim($forgotPasswordRequest->email));
        $emailLimit = $this->forgotPasswordEmailLimiter->create($normalizedEmail)->consume();

        if (!$emailLimit->isAccepted()) {
            return $responder->error(
                code: 'rate_limit_exceeded',
                message: 'Too many requests. Please try again later.',
                status: 429,
            );
        }
        // Тест rate-limiter -- END --

        $result = $handler(new ForgotPasswordCommand(
            email: $forgotPasswordRequest->email,
        ));

        return $responder->success(
            data: [
                'email' => $result->email,
            ],
            message: $result->message
        );
    }
}
