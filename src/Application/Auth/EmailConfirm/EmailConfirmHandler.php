<?php

declare(strict_types=1);

namespace App\Application\Auth\EmailConfirm;

use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;

final readonly class EmailConfirmHandler
{
    public function __construct(
        private DomainUserRepositoryInterface $domainUserRepository,
    ) {
    }

    public function __invoke(EmailConfirmCommand $command): EmailConfirmResult
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

        return new EmailConfirmResult(
            email: $user->getEmail(),
            message: 'Your email has been verified.'
        );
    }
}
