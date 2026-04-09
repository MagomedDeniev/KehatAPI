<?php

declare(strict_types=1);

namespace App\Application\Auth\ConfirmEmail;

use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;

final readonly class ConfirmEmailHandler
{
    public function __construct(
        private DomainUserRepositoryInterface $domainUserRepository,
    ) {
    }

    public function __invoke(ConfirmEmailCommand $command): ConfirmEmailResult
    {
        $user = $this->domainUserRepository->findUserByEmailToken($command->token);

        if (!$user instanceof DomainUser) {
            throw new \DomainException('Invalid email confirmation token.');
        }

        if (!$user->hasValidEmailToken()) {
            throw new \DomainException('Email confirmation token is invalid or expired.');
        }

        $user->confirmEmail();
        $this->domainUserRepository->updateDomainUser($user);

        return new ConfirmEmailResult(
            email: $user->getEmail(),
            message: 'Your email has been verified.'
        );
    }
}
