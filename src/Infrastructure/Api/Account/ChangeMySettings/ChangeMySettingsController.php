<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Account\ChangeMySettings;

use App\Application\Account\ChangeMySettings\ChangeMySettingsCommand;
use App\Application\Account\ChangeMySettings\ChangeMySettingsHandler;
use App\Domain\Enum\GenderEnum;
use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Infrastructure\Service\JsonResponder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ChangeMySettingsController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/api/me/settings', name: 'api_change_my_settings', methods: ['PATCH'])]
    public function changeMySettings(#[CurrentUser] User $user, #[MapRequestPayload] ChangeMySettingsRequest $changeMeRequest, ChangeMySettingsHandler $handler, JsonResponder $responder, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        if (null === $user->getId()) {
            throw $this->createAccessDeniedException('User not have id.');
        }

        $result = $handler(new ChangeMySettingsCommand(
            userId: $user->getId(),
            username: $changeMeRequest->username,
            gender: GenderEnum::from( $changeMeRequest->gender),
            birthDate: \DateTimeImmutable::createFromFormat('Y-m-d', $changeMeRequest->birthDate),
            email: $changeMeRequest->email,
        ));

        return $responder->success(
            data: ['token' => $result->emailUpdated ? $JWTManager->create($user) : null],
            message: $result->message
        );
    }
}
