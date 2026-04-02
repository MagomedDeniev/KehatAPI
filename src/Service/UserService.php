<?php

namespace App\Service;

use App\DTO\Request\ChangeMeRequest;
use App\DTO\Request\RegisterRequest;
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
        private int                         $tokenTimeOutSeconds
    ){}

    /**
     * @throws TransportExceptionInterface
     */
    public function registerFromDto(RegisterRequest $dto): void
    {
        $user = new User();
        $user->setUsername($dto->username);
        $user->setEmail($dto->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
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

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user,$plainPassword);
    }

    public function updatePassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->clearToken();

        $this->em->flush();
    }

    public function updateProfile(User $user, ChangeMeRequest $dto): void
    {
        $user->setUsername($dto->username);
        $user->setEmail($dto->email);

        $this->em->flush();
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendConfirmationToken(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user instanceof User) {
            $user->refreshConfirmationToken($this->tokenGenerator->generateToken());
            $this->em->flush();

            $this->mailer->sendTemplate(
                to: (string) $user->getEmail(),
                subject: 'Восстановление аккаунта',
                template: 'mailer/reset_password.html.twig',
                context: ['user' => $user],
            );
        }
    }

    public function tokenIsValid(string $token): bool|User
    {
        $user = $this->userRepository->findOneBy(['token' => $token]);

        if (!$user instanceof User) {
            return false;
        }

        if (!$user->hasValidEmailConfirmationToken($this->tokenTimeOutSeconds)) {
            return false;
        }

        return $user;
    }

    public function confirmEmailIfTokenIsValid(string $token): bool
    {
        if ($user = $this->tokenIsValid($token)) {
            $user->confirmEmail();
            $this->em->flush();

            return true;
        } else {
            return false;
        }
    }
}
