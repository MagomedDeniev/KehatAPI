<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\Register;

use App\Application\Auth\Register\RegisterCommand;
use App\Application\Auth\Register\RegisterHandler;
use App\Infrastructure\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $registerRequest, RegisterHandler $handler, JsonResponder $responder): JsonResponse
    {
        $result = $handler(new RegisterCommand(
            email: $registerRequest->email,
            username: $registerRequest->username,
            password: $registerRequest->password,
        ));

        return $responder->created(
            data: [
                'userId' => $result->userId,
                'email' => $result->email,
            ],
            message: $result->message,
        );
    }
}
