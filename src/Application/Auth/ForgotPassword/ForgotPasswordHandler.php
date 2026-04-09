<?php

declare(strict_types=1);

namespace App\Application\Auth\ForgotPassword;

use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PasswordToken;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Infrastructure\Service\MailerService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final readonly class ForgotPasswordHandler
{
    public function __construct(
        private DomainUserRepositoryInterface $domainUserRepository,
        private TokenGeneratorInterface $tokenGenerator,
        private MailerService $mailerService,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(ForgotPasswordCommand $command): ForgotPasswordResult
    {
        $email = (string) new Email($command->email);
        $user = $this->domainUserRepository->findUserByEmail($email);

        if ($user instanceof DomainUser) {
            $token = new PasswordToken($this->tokenGenerator->generateToken());
            $tokenExpiresAt = new TokenExpirationTime();

            $user->assignPasswordToken($token, $tokenExpiresAt);

            $this->domainUserRepository->updateDomainUser($user);

            $this->mailerService->sendTemplate(
                to: $user->getEmail(),
                subject: 'Account recovery',
                template: 'mailer/recovery_password.html.twig',
                context: ['user' => $user]
            );
        }

        return new ForgotPasswordResult(
            email: $email,
            message: 'If email is valid, you will receive a link to reset your password.',
        );
    }
}
