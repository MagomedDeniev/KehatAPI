<?php

namespace App\Service;

use App\DTO\Request\ChangeMeRequest;
use App\DTO\Request\EmailVerifyRequest;
use App\DTO\Request\ForgotPasswordRestoreRequest;
use App\DTO\Request\RegisterRequest;
use App\Entity\User;
use App\Repository\TokenRepository;
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
        private MailerService               $mailerService,
        private TokenService                $tokenService
    ){}

    /**
     * @throws TransportExceptionInterface
     */
    public function register(RegisterRequest $dto): void
    {
        $user = new User();
        $user->setEmail($dto->email);
        $user->setUsername($dto->username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        $this->em->persist($user);
        $this->em->flush();

        $token = $this->tokenService->createEmailConfirmationToken($user);

        $this->mailerService->sendTemplate(
            to: (string) $user->getEmail(),
            subject: 'Подтверждение электронной почты',
            template: 'mailer/registration.html.twig',
            context: [
                'user' => $user,
                'token' => $token,
            ],
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendPasswordRecoveryEmail(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user instanceof User) {
            $token = $this->tokenService->createPasswordRecoveryToken($user);

            $this->mailerService->sendTemplate(
                to: (string) $user->getEmail(),
                subject: 'Восстановление аккаунта',
                template: 'mailer/recovery_password.html.twig',
                context: [
                    'user' => $user,
                    'token' => $token,
                ],
            );
        }
    }

    public function updateProfile(User $user, ChangeMeRequest $dto): void
    {
        $user->setUsername($dto->username);
        $user->setEmail($dto->email);

        $this->em->flush();
    }

    public function updatePasswordFromUser(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->em->flush();
    }

    public function updatePasswordFromToken(ForgotPasswordRestoreRequest $dto): void
    {
        $token = $this->tokenService->getToken($dto->token);
        $user = $this->userRepository->findOneBy(['id' => $token->getUserId()]);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->newPassword));
        $this->tokenService->removeToken($token, false);

        $this->em->flush();
    }

    public function confirmEmailIfTokenIsValid(EmailVerifyRequest $dto): void
    {
        $token = $this->tokenService->getToken($dto->token);
        $user = $this->userRepository->findOneBy(['id' => $token->getUserId()]);
        $user->setConfirmedEmail($token->getEmail());
        $this->tokenService->removeToken($token, false);

        $this->em->flush();
    }
}
