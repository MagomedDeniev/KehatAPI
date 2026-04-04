<?php

namespace App\Service;

use App\DTO\Request\ChangeMeRequest;
use App\DTO\Request\EmailVerifyRequest;
use App\DTO\Request\ForgotPasswordRestoreRequest;
use App\DTO\Request\RegisterRequest;
use App\Entity\User;
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

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function normalizeUsername(string $username): string
    {
        return trim($username);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function register(RegisterRequest $dto): void
    {
        $user = new User();
        $user->setEmail($this->normalizeEmail($dto->email));
        $user->setUsername($this->normalizeUsername($dto->username));
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        $user->setEmailToken($this->tokenGenerator->generateToken());
        $user->setEmailTokenExpiresAt($this->tokenExpiresAt());
        $this->em->persist($user);
        $this->em->flush();

        $this->mailerService->sendTemplate(
            to: (string) $user->getEmail(),
            subject: 'Welcome message',
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
                subject: 'Account recovery',
                template: 'mailer/recovery_password.html.twig',
                context: ['user' => $user]
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function updateProfile(User $user, ChangeMeRequest $dto): void
    {
        $emailIsNotConfirmed = $user->getConfirmedEmail() !== $this->normalizeEmail($dto->email);

        if ($emailIsNotConfirmed) {
            $user->setEmailToken($this->tokenGenerator->generateToken());
            $user->setEmailTokenExpiresAt($this->tokenExpiresAt());
        }

        $user->setUsername($this->normalizeUsername($dto->username));
        $user->setEmail($this->normalizeEmail($dto->email));
        $this->em->flush();

        if ($emailIsNotConfirmed) {
            $this->mailerService->sendTemplate(
                to: (string) $user->getEmail(),
                subject: 'Email verification',
                template: 'mailer/email_confirmation.html.twig',
                context: ['user' => $user]
            );
        }
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
        $user->clearToken('password');
        $this->em->flush();
    }

    public function confirmEmailWithToken(EmailVerifyRequest $dto): void
    {
        $user = $this->userRepository->findOneBy(['emailToken' => $dto->token]);
        $user->setConfirmedEmail($user->getEmail());
        $user->clearToken('email');
        $this->em->flush();
    }
}
