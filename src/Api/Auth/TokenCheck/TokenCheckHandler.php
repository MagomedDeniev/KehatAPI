<?php

declare(strict_types=1);

namespace App\Api\Auth\TokenCheck;

use App\Doctrine\Entity\User;
use App\Doctrine\Repository\UserRepository;

final readonly class TokenCheckHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(TokenCheckCommand $command): TokenCheckResult
    {
        if ('password' === $command->type) {
            $user = $this->userRepository->findUserByPasswordToken($command->token);
        } elseif ('email' === $command->type) {
            $user = $this->userRepository->findUserByEmailToken($command->token);
        } else {
            $user = null;
        }

        if (!$user instanceof User) {
            throw new \DomainException('Token is invalid.');
        }

        if ('password' === $command->type) {
            if (!$user->hasValidPasswordToken()) {
                throw new \DomainException('Password reset token is invalid or expired.');
            }
        } elseif ('email' === $command->type) {
            if (!$user->hasValidEmailToken()) {
                throw new \DomainException('Email reset token is invalid or expired.');
            }
        }

        return new TokenCheckResult(
            message: 'Token is valid.'
        );
    }
}
