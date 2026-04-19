<?php

declare(strict_types=1);

namespace App\Api\Account\PasswordChange;

use App\Doctrine\Entity\User;
use App\Doctrine\Repository\UserRepository;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\Password;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class PasswordChangeHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(PasswordChangeCommand $command): PasswordChangeResult
    {
        $currentPassword = $command->currentPassword;
        $newPassword = new Password($command->newPassword);

        $user = $this->userRepository->findUserById($command->userId);

        if (!$user instanceof User) {
            throw new \DomainException('User not found.');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new \DomainException('Current password is incorrect.');
        }

        $newHashedPassword = new HashedPassword($this->passwordHasher->hashPassword($user,(string) $newPassword));
        $user->changePassword($newHashedPassword);
        $this->userRepository->updateUser($user);

        return new PasswordChangeResult(
            message: 'Password updated successfully'
        );
    }
}
