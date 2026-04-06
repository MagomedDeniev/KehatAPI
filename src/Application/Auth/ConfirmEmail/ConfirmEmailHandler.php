<?php

namespace App\Application\Auth\ConfirmEmail;

use App\Infrastructure\Doctrine\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ConfirmEmailHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    ) {}

    public function __invoke(ConfirmEmailCommand $command): ConfirmEmailResult
    {
        $user = $this->userRepository->findOneBy(['emailToken' => $command->token]);

        if ($user === null) {
            throw new \DomainException('Invalid email confirmation token.');
        }

        $user->setConfirmedEmail($user->getEmail());
        $user->clearToken('email');
        $this->em->flush();

        return new ConfirmEmailResult(
            email: $user->getEmail(),
            message: 'Your email has been verified.'
        );
    }
}
