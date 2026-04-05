<?php

namespace App\Application\Auth\ForgotPassword;

use App\Domain\ValueObject\TokenExpirationTime;
use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Infrastructure\Service\MailerService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Throwable;

final readonly class ForgotPasswordHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private TokenGeneratorInterface $tokenGenerator,
        private MailerService $mailerService
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception|Throwable
     */
    public function __invoke(ForgotPasswordCommand $command): ForgotPasswordResult
    {
        $user = $this->userRepository->findOneBy(['email' => $command->email]);

        if ($user instanceof User) {
            $tokenExpiresAt = new TokenExpirationTime();

            $user->setPasswordToken($this->tokenGenerator->generateToken());
            $user->setPasswordTokenExpiresAt($tokenExpiresAt->value());

            $connection = $this->em->getConnection();
            $connection->beginTransaction();

            try {
                $this->em->flush();

                $this->mailerService->sendTemplate(
                    to: (string) $user->getEmail(),
                    subject: 'Account recovery',
                    template: 'mailer/recovery_password.html.twig',
                    context: ['user' => $user]
                );

                $connection->commit();
            } catch (Throwable $e) {
                $connection->rollBack();
                throw $e;
            }
        }

        return new ForgotPasswordResult(
            email: $command->email,
            message: 'If email is valid, you will receive a link to reset your password.',
        );
    }
}
