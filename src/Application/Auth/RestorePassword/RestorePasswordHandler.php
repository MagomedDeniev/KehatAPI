<?php

declare(strict_types=1);

namespace App\Application\Auth\RestorePassword;

use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;

final readonly class RestorePasswordHandler
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
        private DomainUserRepositoryInterface $domainUserRepository,
    ) {
    }

    public function __invoke(RestorePasswordCommand $command): RestorePasswordResult
    {
        $user = $this->domainUserRepository->findUserBy(['passwordToken' => $command->token]);

        if (!$user instanceof DomainUser) {
            throw new \DomainException('Invalid password reset token.');
        }

        $user->restorePassword($this->passwordHasher->hash($command->password));
        $this->domainUserRepository->saveDomainUser($user);

        return new RestorePasswordResult(
            userId: (int) $user->getId(),
            message: 'Your password has been restored, you can login now.'
        );
    }
}
