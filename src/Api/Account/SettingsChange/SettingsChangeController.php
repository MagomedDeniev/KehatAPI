<?php

declare(strict_types=1);

namespace App\Api\Account\SettingsChange;

use App\Doctrine\Entity\User;
use App\Domain\Enum\GenderEnum;
use App\Service\JsonResponder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SettingsChangeController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/api/account/settings', name: 'api_account_settings_change', methods: ['PATCH'])]
    public function settingsChange(#[CurrentUser] User $user, #[MapRequestPayload] SettingsChangeRequest $changeMeRequest, SettingsChangeHandler $handler, JsonResponder $responder, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        if (null === $user->getId()) {
            throw $this->createAccessDeniedException('User not have id.');
        }

        $birthDate = \DateTimeImmutable::createFromFormat('Y-m-d', $changeMeRequest->birthDate);
        if (!$birthDate instanceof \DateTimeImmutable) {
            throw new \LogicException('Birth date must use Y-m-d format.');
        }

        $result = $handler(new SettingsChangeCommand(
            userId: $user->getId(),
            username: $changeMeRequest->username,
            gender: GenderEnum::from($changeMeRequest->gender),
            birthDate: $birthDate,
            email: $changeMeRequest->email,
        ));

        return $responder->success(
            data: ['token' => $result->emailUpdated ? $JWTManager->create($user) : null],
            message: $result->message
        );
    }
}
