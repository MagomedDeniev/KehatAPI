<?php

namespace App\Application\Auth\Register;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Domain\ValueObject\Username;
use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Service\MailerService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Throwable;

final readonly class RegisterHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenGeneratorInterface $tokenGenerator,
        private MailerService $mailer
    ) {}

    /**
     * @throws TransportExceptionInterface
     * @throws Exception|Throwable
     */
    public function __invoke(RegisterCommand $command): RegisterResult
    {
        $email = new Email($command->email);
        $username = new Username($command->username);
        $tokenExpiresAt = new TokenExpirationTime();

        $user = new User();
        $user->setEmail($email->value());
        $user->setUsername($username->value());
        $user->setPassword($this->passwordHasher->hashPassword($user, $command->plainPassword));
        $user->setEmailToken($this->tokenGenerator->generateToken());
        $user->setEmailTokenExpiresAt($tokenExpiresAt->value());

        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $this->em->persist($user);
            $this->em->flush();

            $this->mailer->sendTemplate(
                to: (string) $user->getEmail(),
                subject: 'Welcome message',
                template: 'mailer/registration.html.twig',
                context: ['user' => $user],
            );

            $connection->commit();
        } catch (Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        return new RegisterResult(
            userId: (int) $user->getId(),
            email: (string) $user->getEmail(),
            message: 'User successfully registered, check your email for further instructions.',
        );
    }
}
