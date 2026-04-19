<?php

declare(strict_types=1);

namespace App\Api\Auth\PasswordRestore;

use App\Doctrine\Entity\User;
use App\Doctrine\Repository\UserRepository;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\Password;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class PasswordRestoreHandler
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(PasswordRestoreCommand $command): PasswordRestoreResult
    {
        $user = $this->userRepository->findUserByPasswordToken($command->token);

        if (!$user instanceof User) {
            throw new \DomainException('Invalid password reset token.');
        }

        if (!$user->hasValidPasswordToken()) {
            throw new \DomainException('Password reset token is invalid or expired.');
        }

        $password = new Password($command->password);
        $hashedPassword = new HashedPassword($this->passwordHasher->hashPassword($user, (string) $password));

        $user->changePassword($hashedPassword);
        $this->userRepository->updateUser($user);

        return new PasswordRestoreResult(
            userId: $user->getId() ?? throw new \LogicException('Registered user must have id.'),
            message: 'Your password has been restored, you can login now.'
        );
    }
}
