<?php

declare(strict_types=1);

namespace App\Api\Auth\EmailConfirm;

use App\Doctrine\Entity\User;
use App\Doctrine\Repository\UserRepository;

final readonly class EmailConfirmHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(EmailConfirmCommand $command): EmailConfirmResult
    {
        $user = $this->userRepository->findUserByEmailToken($command->token);

        if (!$user instanceof User) {
            throw new \DomainException('Invalid email confirmation token.');
        }

        if (!$user->hasValidEmailToken()) {
            throw new \DomainException('Email confirmation token is invalid or expired.');
        }

        $user->confirmEmail();
        $this->userRepository->updateUser($user);

        return new EmailConfirmResult(
            message: 'Your email has been verified.'
        );
    }
}
