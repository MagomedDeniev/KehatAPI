<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Account\ChangeMySettings;

use App\Application\Account\ChangeMySettings\ChangeMySettingsCommand;
use App\Application\Account\ChangeMySettings\ChangeMySettingsHandler;
use App\Infrastructure\Doctrine\Entity\User;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ChangeMySettingsController extends AbstractController
{
    /**
     * @throws \Throwable
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    #[Route('/api/me/settings', name: 'api_change_my_settings', methods: ['PATCH'])]
    public function changeMySettings(#[CurrentUser] User $user, #[MapRequestPayload] ChangeMySettingsRequest $changeMeRequest, ChangeMySettingsHandler $handler): JsonResponse
    {
        if (null === $user->getId()) {
            throw $this->createAccessDeniedException('User not have id.');
        }

        $result = $handler(new ChangeMySettingsCommand(
            userId: $user->getId(),
            username: $changeMeRequest->username,
            email: $changeMeRequest->email,
        ));

        return $this->json([
            'success' => true,
            'data' => [],
            'message' => $result->message,
        ], 201);
    }
}
