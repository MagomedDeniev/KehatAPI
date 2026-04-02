<?php

namespace App\Controller\API;

use App\DTO\Request\ForgotPasswordRestoreRequest;
use App\DTO\Request\RegisterRequest;
use App\DTO\Request\ForgotPasswordRequest;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
final class AuthController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, ValidatorInterface $validator, UserService $userService): JsonResponse
    {
        $data = $request->toArray();

        $dto = new RegisterRequest();
        $dto->username = $data['username'] ?? '';
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $this->formatValidationErrors($errors),
            ], 422);
        }

        $userService->registerFromDto($dto);

        return $this->json([
            'success' => true,
            'message' => 'Пользователь зарегистрирован. Подтвердите почту.',
        ], 201);
    }

    private function formatValidationErrors(iterable $errors): array
    {
        $result = [];

        foreach ($errors as $error) {
            $result[$error->getPropertyPath()][] = $error->getMessage();
        }

        return $result;
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/password/forgot', name: 'password_forgot', methods: ['POST'])]
    public function passwordForgot(Request $request, ValidatorInterface $validator, UserService $userService): JsonResponse
    {
        $data = $request->toArray();

        $dto = new ForgotPasswordRequest();
        $dto->email = $data['email'] ?? '';

        $errors = [];

        foreach ($validator->validate($dto) as $error) {
            $errors[$error->getPropertyPath()][] = $error->getMessage();
        }

        if ($errors) {
            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }

        $userService->sendConfirmationToken($dto->email);

        return $this->json([
            'success' => true,
            'message' => 'Если почта верна, то на нее будет отправлено письмо для восстановления пароля.',
        ]);
    }

    #[Route('/password/reset/{token}', name: 'password_reset', methods: ['POST'])]
    public function passwordReset(string $token, Request $request, ValidatorInterface $validator, UserService $userService): JsonResponse {
        $data = $request->toArray();

        $dto = new ForgotPasswordRestoreRequest();
        $dto->newPassword = $data['newPassword'] ?? '';
        $dto->repeatPassword = $data['repeatPassword'] ?? '';

        $errors = [];

        foreach ($validator->validate($dto) as $error) {
            $errors[$error->getPropertyPath()][] = $error->getMessage();
        }

        if ($dto->newPassword !== $dto->repeatPassword) {
            $errors['repeatPassword'][] = 'Passwords do not match.';
        }

        if ($errors) {
            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }

        if ($user = $userService->tokenIsValid($token)) {
            $userService->updatePassword($user, $dto->newPassword);

            return $this->json([
                'success' => true,
                'message' => 'Ваш пароль изменен, можете войти в аккаунт используя новый пароль.',
            ]);
        } else {
            $errors['linkNotValid'][] = 'Ссылка восстановления пароля недействительна или срок её действия истёк, повторите попытку.';

            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }
    }

    #[Route('/email/verify/{token}', name: 'email_verify', methods: ['POST'])]
    public function emailVerify(string $token, Request $request, ValidatorInterface $validator, UserService $userService): JsonResponse {
        if ($userService->confirmEmailIfTokenIsValid($token)) {
            return $this->json([
                'success' => true,
                'message' => 'Вы успешно подтвердили свою почту.',
            ]);
        } else {
            $errors['linkNotValid'][] = 'Ссылка подтверждения электронной почты недействительна или срок её действия истёк, повторите попытку.';

            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }
    }
}
