<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final readonly class UserService
{
    public function __construct(
        private EntityManagerInterface      $em,
        private UserRepository              $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenGeneratorInterface     $tokenGenerator,
        private MailerService               $mailer,
        private int                         $timeOutSeconds
    ){}

    /**
     * @throws TransportExceptionInterface
     */
    public function register(User $user, string $plainPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);

        $user->changePassword($hashedPassword);
        $user->refreshConfirmationToken($this->tokenGenerator->generateToken());

        $this->em->persist($user);
        $this->em->flush();

        $this->mailer->sendTemplate(
            to: (string) $user->getEmail(),
            subject: 'Подтверждение электронной почты',
            template: 'mailer/registration.html.twig',
            context: ['user' => $user],
        );
    }

    public function confirmEmailIfValid(string $token): bool
    {
        $user = $this->userRepository->findOneBy(['token' => $token]);

        if (!$user instanceof User) {
            return false;
        }

        if (!$user->hasValidEmailConfirmationToken($this->timeOutSeconds)) {
            return false;
        }

        $user->confirmEmail();
        $this->em->flush();

        return true;
    }
}
