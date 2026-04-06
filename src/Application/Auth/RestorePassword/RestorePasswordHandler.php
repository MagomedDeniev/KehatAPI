<?php

declare(strict_types=1);

namespace App\Application\Auth\RestorePassword;

use App\Infrastructure\Doctrine\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RestorePasswordHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(RestorePasswordCommand $command): RestorePasswordResult
    {
        $user = $this->userRepository->findOneBy(['passwordToken' => $command->token]);

        if (null === $user) {
            throw new \DomainException('Invalid password reset token.');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $command->password));
        $user->clearToken('password');
        $this->em->flush();

        return new RestorePasswordResult(
            userId: (int) $user->getId(),
            message: 'Your password has been restored, you can login now.'
        );
    }
}
