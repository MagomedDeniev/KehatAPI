<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\Register;

use App\Application\Auth\Register\RegisterCommand;
use App\Application\Auth\Register\RegisterHandler;
use App\Domain\Enum\GenderEnum;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Infrastructure\Service\JsonResponder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
    public function register(#[MapRequestPayload] RegisterRequest $registerRequest, RegisterHandler $handler, JsonResponder $responder, UserRepository $userRepository, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $result = $handler(new RegisterCommand(
            username: $registerRequest->username,
            gender: GenderEnum::from($registerRequest->gender),
            birthDate: \DateTimeImmutable::createFromFormat('Y-m-d', $registerRequest->birthDate),
            email: $registerRequest->email,
            password: $registerRequest->password,
        ));

        $user = $userRepository->findOneBy(['id' => $result->userId]);
        $token = $JWTManager->create($user);

        return $responder->created(
            data: ['token' => $token],
            message: $result->message,
        );
    }
}
