<?php

declare(strict_types=1);

namespace App\Api\Auth\Register;

use App\Doctrine\Entity\User;
use App\Doctrine\Repository\UserRepository;
use App\Domain\Enum\GenderEnum;
use App\Service\JsonResponder;
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
    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $registerRequest, RegisterHandler $handler, JsonResponder $responder, UserRepository $userRepository, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $birthDate = \DateTimeImmutable::createFromFormat('Y-m-d', $registerRequest->birthDate);
        if (!$birthDate instanceof \DateTimeImmutable) {
            throw new \LogicException('Birth date must use Y-m-d format.');
        }

        $result = $handler(new RegisterCommand(
            username: $registerRequest->username,
            gender: GenderEnum::from($registerRequest->gender),
            birthDate: $birthDate,
            email: $registerRequest->email,
            password: $registerRequest->password,
        ));

        $user = $userRepository->findOneBy(['id' => $result->userId]);
        if (!$user instanceof User) {
            throw new \LogicException('Registered user was not found.');
        }

        $token = $JWTManager->create($user);

        return $responder->created(
            data: ['token' => $token],
            message: $result->message,
        );
    }
}
