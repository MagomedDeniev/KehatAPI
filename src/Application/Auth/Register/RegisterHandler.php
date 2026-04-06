<?php

declare(strict_types=1);

namespace App\Application\Auth\Register;

use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Domain\ValueObject\Username;
use App\Infrastructure\Service\MailerService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final readonly class RegisterHandler
{
    public function __construct(
        private DomainUserRepositoryInterface $domainUserRepository,
        private PasswordHasherInterface $passwordHasher,
        private TokenGeneratorInterface $tokenGenerator,
        private MailerService $mailer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(RegisterCommand $command): RegisterResult
    {
        $email = new Email($command->email);
        $username = new Username($command->username);
        $tokenExpiresAt = new TokenExpirationTime();

        $user = DomainUser::register(
            email: $email->value(),
            password: $this->passwordHasher->hash($command->plainPassword),
            username: $username->value(),
            emailToken: $this->tokenGenerator->generateToken(),
            emailTokenExpiresAt: $tokenExpiresAt->value(),
        );

        $user = $this->domainUserRepository->saveDomainUser($user);

        $this->mailer->sendTemplate(
            to: $user->getEmail(),
            subject: 'Welcome message',
            template: 'mailer/registration.html.twig',
            context: ['user' => $user],
        );

        return new RegisterResult(
            userId: $user->getId() ?? throw new \LogicException('Registered user must have id.'),
            email: $user->getEmail(),
            message: 'User successfully registered, check your email for further instructions.',
        );
    }
}
