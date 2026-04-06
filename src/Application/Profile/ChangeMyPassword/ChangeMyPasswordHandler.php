<?php

declare(strict_types=1);

namespace App\Application\Profile\ChangeMyPassword;

use App\Infrastructure\Doctrine\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ChangeMyPasswordHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(ChangeMyPasswordCommand $command): ChangeMyPasswordResult
    {
        $user = $this->userRepository->findOneBy(['id' => $command->userId]);

        if (null === $user) {
            throw new \DomainException('User not found.');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $command->password));
        $this->em->flush();

        return new ChangeMyPasswordResult(
            message: 'Password updated successfully'
        );
    }
}
