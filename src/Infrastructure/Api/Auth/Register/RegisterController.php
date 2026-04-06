<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\Register;

use App\Application\Auth\Register\RegisterCommand;
use App\Application\Auth\Register\RegisterHandler;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
{
    /**
     * @throws \Throwable
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $registerRequest, RegisterHandler $handler): JsonResponse
    {
        $result = $handler(new RegisterCommand(
            email: $registerRequest->email,
            username: $registerRequest->username,
            plainPassword: $registerRequest->password,
        ));

        return $this->json([
            'success' => true,
            'data' => [
                'userId' => $result->userId,
                'email' => $result->email,
            ],
            'message' => $result->message,
        ], 201);
    }
}
