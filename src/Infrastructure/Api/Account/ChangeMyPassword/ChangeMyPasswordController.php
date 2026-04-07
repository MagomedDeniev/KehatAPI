<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Account\ChangeMyPassword;

use App\Application\Account\ChangeMyPassword\ChangeMyPasswordCommand;
use App\Application\Account\ChangeMyPassword\ChangeMyPasswordHandler;
use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Service\JsonResponder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ChangeMyPasswordController extends AbstractController
{
    #[Route('/api/me/password', name: 'api_change_password', methods: ['PATCH'])]
    public function changePassword(#[CurrentUser] ?User $user, #[MapRequestPayload] ChangeMyPasswordRequest $changePasswordRequest, ChangeMyPasswordHandler $handler, JsonResponder $responder): JsonResponse
    {
        if (!$user instanceof User || null === $user->getId()) {
            throw $this->createAccessDeniedException('User is not authenticated or not have id.');
        }

        $result = $handler(new ChangeMyPasswordCommand(
            userId: $user->getId(),
            password: $changePasswordRequest->newPassword
        ));

        return $responder->success(
            message: $result->message
        );
    }
}
