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
        if ('password' === $command->type) {
            $user = $this->domainUserRepository->findUserByPasswordToken($command->token);
        } elseif ('email' === $command->type) {
            $user = $this->domainUserRepository->findUserByEmailToken($command->token);
        } else {
            $user = null;
        }

        if (!$user instanceof DomainUser) {
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

        return new CheckTokenResult(
            message: 'Token is valid.'
        );
    }
}
