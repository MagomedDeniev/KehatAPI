<?php

declare(strict_types=1);

namespace App\Application\Auth\CheckToken;

use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;

final readonly class CheckTokenHandler
{
    public function __construct(
        private DomainUserRepositoryInterface $domainUserRepository,
    ) {
    }

    public function __invoke(CheckTokenCommand $command): CheckTokenResult
    {
        if ($command->type === "password") {
            $user = $this->domainUserRepository->findUserByPasswordToken($command->token);
        } elseif ($command->type === "email") {
            $user = $this->domainUserRepository->findUserByEmailToken($command->token);
        } else {
            $user = null;
        }

        if (!$user instanceof DomainUser) {
            throw new \DomainException('Token is invalid.');
        }

        if ($command->type === "password") {
            if (!$user->hasValidPasswordToken()) {
                throw new \DomainException('Password reset token is invalid or expired.');
            }
        } elseif ($command->type === "email") {
            if (!$user->hasValidEmailToken()) {
                throw new \DomainException('Email reset token is invalid or expired.');
            }
        }

        return new CheckTokenResult(
            message: 'Token is valid.'
        );
    }
}
