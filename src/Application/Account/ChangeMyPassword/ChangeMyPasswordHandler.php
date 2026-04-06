<?php

declare(strict_types=1);

namespace App\Application\Account\ChangeMyPassword;

use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;

final readonly class ChangeMyPasswordHandler
{
    public function __construct(
        private DomainUserRepositoryInterface $domainUserRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(ChangeMyPasswordCommand $command): ChangeMyPasswordResult
    {
        $user = $this->domainUserRepository->findUserBy(['id' => $command->userId]);

        if (!$user instanceof DomainUser) {
            throw new \DomainException('User not found.');
        }

        $user->setPassword($this->passwordHasher->hash($command->password));
        $this->domainUserRepository->saveDomainUser($user);

        return new ChangeMyPasswordResult(
            message: 'Password updated successfully'
        );
    }
}
