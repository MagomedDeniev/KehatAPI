<?php

declare(strict_types=1);

namespace App\Application\Auth\Register;

use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EmailToken;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\Password;
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

        if ($this->domainUserRepository->findUserByEmail((string) $email) instanceof DomainUser) {
            throw new \DomainException('There is already an account with this email.');
        }

        if ($this->domainUserRepository->findUserByUsername((string) $username) instanceof DomainUser) {
            throw new \DomainException('There is already an account with this username.');
        }

        $password = new Password($command->password);
        $hashedPassword = new HashedPassword($this->passwordHasher->hash((string) $password));
        $emailToken = new EmailToken($this->tokenGenerator->generateToken());
        $emailTokenExpiresAt = new TokenExpirationTime();

        $user = DomainUser::register(
            email: $email,
            password: $hashedPassword,
            username: $username,
            emailToken: $emailToken,
            emailTokenExpiresAt: $emailTokenExpiresAt,
        );

        $user = $this->domainUserRepository->createDomainUser($user);

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
