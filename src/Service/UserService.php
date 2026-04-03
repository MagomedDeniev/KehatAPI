<?php

namespace App\Service;

use App\DTO\Request\ChangeMeRequest;
use App\DTO\Request\EmailVerifyRequest;
use App\DTO\Request\ForgotPasswordRestoreRequest;
use App\DTO\Request\RegisterRequest;
use App\Entity\User;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
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
        private MailerService               $mailerService,
        private int                         $tokenTimeOutSeconds
    ){}

    private function tokenExpiresAt(): DateTimeImmutable
    {
        return(new DateTimeImmutable())->modify(sprintf('+%d seconds', $this->tokenTimeOutSeconds));
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function register(RegisterRequest $dto): void
    {
        $user = new User();
        $user->setEmail($dto->email);
        $user->setUsername($dto->username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        $user->setEmailToken($this->tokenGenerator->generateToken());
        $user->setEmailTokenExpiresAt($this->tokenExpiresAt());
        $this->em->persist($user);
        $this->em->flush();

        $this->mailerService->sendTemplate(
            to: (string) $user->getEmail(),
            subject: 'Подтверждение электронной почты',
            template: 'mailer/registration.html.twig',
            context: ['user' => $user]
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendPasswordRecoveryEmail(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user instanceof User) {
            $user->setPasswordToken($this->tokenGenerator->generateToken());
            $user->setPasswordTokenExpiresAt($this->tokenExpiresAt());
            $this->em->flush();

            $this->mailerService->sendTemplate(
                to: (string) $user->getEmail(),
                subject: 'Восстановление аккаунта',
                template: 'mailer/recovery_password.html.twig',
                context: ['user' => $user]
            );
        }
    }

    public function updateProfile(User $user, ChangeMeRequest $dto): void
    {
        $user->setUsername($dto->username);
        $user->setEmail($dto->email);

        $this->em->flush();
    }

    public function updatePassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->em->flush();
    }

    public function updatePasswordWithToken(ForgotPasswordRestoreRequest $dto): void
    {
        $user = $this->userRepository->findOneBy(['passwordToken' => $dto->token]);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->newPassword));
        $this->em->flush();
    }

    public function confirmEmailWithToken(EmailVerifyRequest $dto): void
    {
        $user = $this->userRepository->findOneBy(['emailToken' => $dto->token]);
        $user->setConfirmedEmail($user->getEmail());
        $this->em->flush();
    }
}
