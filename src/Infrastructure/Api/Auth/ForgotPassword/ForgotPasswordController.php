<?php

namespace App\Infrastructure\Api\Auth\ForgotPassword;

use App\Application\Auth\ForgotPassword\ForgotPasswordCommand;
use App\Application\Auth\ForgotPassword\ForgotPasswordHandler;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class ForgotPasswordController extends AbstractController
{

    /**
     * @throws Throwable
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    #[Route('/api/forgot/password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(#[MapRequestPayload] ForgotPasswordRequest $forgotPasswordRequest, ForgotPasswordHandler $handler): JsonResponse
    {
        $result = $handler(new ForgotPasswordCommand(
            email: $forgotPasswordRequest->email,
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
