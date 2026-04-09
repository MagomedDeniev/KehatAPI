<?php

declare(strict_types=1);

namespace App\Application\Auth\RestorePassword;

use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\Password;

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

        if (!$user->hasValidPasswordToken()) {
            throw new \DomainException('Password reset token is invalid or expired.');
        }

        $password = new Password($command->password);
        $hashedPassword = new HashedPassword($this->passwordHasher->hash((string) $password));

        $user->changePassword($hashedPassword);
        $this->domainUserRepository->updateDomainUser($user);

        return new RestorePasswordResult(
            userId: $user->getId() ?? throw new \LogicException('Registered user must have id.'),
            message: 'Your password has been restored, you can login now.'
        );
    }
}
