<?php

declare(strict_types=1);

namespace App\Application\Account\ChangeMyPassword;

use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Domain\ValueObject\Password;

final readonly class ChangeMyPasswordHandler
{
    public function __construct(
        private DomainUserRepositoryInterface $domainUserRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(ChangeMyPasswordCommand $command): ChangeMyPasswordResult
    {
        $currentPassword = $command->currentPassword;
        $newPassword = (string) new Password($command->newPassword);

        $user = $this->domainUserRepository->findUserBy(['id' => $command->userId]);

        if (!$user instanceof DomainUser) {
            throw new \DomainException('User not found.');
        }

        if (!$this->passwordHasher->verify($user->getPassword(), $currentPassword)) {
            throw new \DomainException('Current password is incorrect.');
        }

        $newHashedPassword = $this->passwordHasher->hash($newPassword);
        $user->changePassword($newHashedPassword);
        $this->domainUserRepository->updateDomainUser($user);

        return new ChangeMyPasswordResult(
            message: 'Password updated successfully'
        );
    }
}
