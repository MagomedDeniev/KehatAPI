<?php

declare(strict_types=1);

namespace App\Api\Auth\Register;

use App\Doctrine\Entity\User;
use App\Doctrine\Repository\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EmailToken;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\Password;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Domain\ValueObject\Username;
use App\Service\MailerService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final readonly class RegisterHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
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

        if ($this->userRepository->findUserByEmail((string) $email) instanceof User) {
            throw new \DomainException('There is already an account with this email.');
        }

        if ($this->userRepository->findUserByUsername((string) $username) instanceof User) {
            throw new \DomainException('There is already an account with this username.');
        }

        $password = new Password($command->password);
        $hashedPassword = new HashedPassword($this->passwordHasher->hashPassword(new User(), (string) $password));
        $emailToken = new EmailToken($this->tokenGenerator->generateToken());
        $emailTokenExpiresAt = new TokenExpirationTime();

        $user = User::register(
            email: $email,
            password: $hashedPassword,
            username: $username,
            emailToken: $emailToken,
            emailTokenExpiresAt: $emailTokenExpiresAt,
            gender: $command->gender,
            birthDate: $command->birthDate
        );

        $hashedPassword = $this->passwordHasher->hashPassword($user, (string) $password);
        $user->changePassword(new HashedPassword($hashedPassword));

        $user = $this->userRepository->createUser($user);

        $this->mailer->sendTemplate(
            to: $user->getEmail(),
            subject: 'Welcome message',
            template: 'mailer/registration.html.twig',
            context: ['user' => $user],
        );

        return new RegisterResult(
            userId: $user->getId() ?? throw new \LogicException('Registered user must have id.'),
            message: 'User successfully registered, check your email for further instructions.',
        );
    }
}
