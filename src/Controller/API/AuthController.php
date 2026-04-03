<?php

namespace App\Controller\API;

use App\DTO\Request\EmailVerifyRequest;
use App\DTO\Request\ForgotPasswordRequest;
use App\DTO\Request\ForgotPasswordRestoreRequest;
use App\DTO\Request\RegisterRequest;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class AuthController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $registerRequest, UserService $userService): JsonResponse
    {
        $userService->register($registerRequest);

        return $this->json([
            'success' => true,
            'message' => 'Пользователь зарегистрирован. Подтвердите почту.',
        ], 201);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/password/forgot', name: 'password_forgot', methods: ['POST'])]
    public function passwordForgot(#[MapRequestPayload] ForgotPasswordRequest $forgotPasswordRequest, UserService $userService): JsonResponse
    {
        $userService->sendPasswordRecoveryEmail($forgotPasswordRequest->email);

        return $this->json([
            'success' => true,
            'message' => 'Если почта верна, то на нее будет отправлено письмо для восстановления пароля.',
        ]);
    }

    #[Route('/password/restore', name: 'password_restore', methods: ['POST'])]
    public function passwordRestore(#[MapRequestPayload] ForgotPasswordRestoreRequest $forgotPasswordRestoreRequest, UserService $userService): JsonResponse {
        $userService->updatePasswordWithToken($forgotPasswordRestoreRequest);

        return $this->json([
            'success' => true,
            'message' => 'Ваш пароль изменен, можете войти в аккаунт используя новый пароль.',
        ]);
    }

    #[Route('/email/verify', name: 'email_verify', methods: ['POST'])]
    public function emailVerify(#[MapRequestPayload] EmailVerifyRequest $emailVerifyRequest, UserService $userService): JsonResponse {
        $userService->confirmEmailWithToken($emailVerifyRequest);

        return $this->json([
            'success' => true,
            'message' => 'Вы успешно подтвердили свою почту.',
        ]);
    }
}
