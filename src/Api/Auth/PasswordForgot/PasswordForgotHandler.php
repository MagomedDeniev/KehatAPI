<?php

declare(strict_types=1);

namespace App\Api\Auth\PasswordForgot;

use App\Doctrine\Entity\User;
use App\Doctrine\Repository\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PasswordToken;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Service\MailerService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final readonly class PasswordForgotHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenGeneratorInterface $tokenGenerator,
        private MailerService $mailerService,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(PasswordForgotCommand $command): PasswordForgotResult
    {
        $email = (string) new Email($command->email);
        $user = $this->userRepository->findUserByEmail($email);

        if ($user instanceof User) {
            $token = new PasswordToken($this->tokenGenerator->generateToken());
            $tokenExpiresAt = new TokenExpirationTime();

            $user->assignPasswordToken($token, $tokenExpiresAt);

            $this->userRepository->updateUser($user);

            $this->mailerService->sendTemplate(
                to: $user->getEmail(),
                subject: 'Account recovery',
                template: 'mailer/recovery_password.html.twig',
                context: ['user' => $user]
            );
        }

        return new PasswordForgotResult(
            email: $email,
            message: 'If email is valid, you will receive a link to reset your password.',
        );
    }
}
