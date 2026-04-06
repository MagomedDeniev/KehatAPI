<?php

namespace App\Application\Profile\ChangeMySettings;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Domain\ValueObject\Username;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Infrastructure\Service\MailerService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Throwable;

final readonly class ChangeMySettingsHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenGeneratorInterface $tokenGenerator,
        private MailerService $mailerService,
        private EntityManagerInterface $em
    ) {}

    /**
     * @throws TransportExceptionInterface
     * @throws Exception|Throwable
     */
    public function __invoke(ChangeMySettingsCommand $command): ChangeMySettingsResult
    {
        $email = new Email($command->email);
        $username = new Username($command->username);
        $tokenExpiresAt = new TokenExpirationTime();

        $user = $this->userRepository->findOneBy(['id' => $command->userId]);

        if ($user === null) {
            throw new \DomainException('User not found.');
        }

        $emailsEquals = $email->value() === $user->getConfirmedEmail();

        if (!$emailsEquals) {
            $user->setEmailToken($this->tokenGenerator->generateToken());
            $user->setEmailTokenExpiresAt($tokenExpiresAt->value());
        }

        $user->setUsername($username->value());
        $user->setEmail($email->value());
        if ($emailsEquals) {
            $this->em->flush();
        }

        if (!$emailsEquals) {
            $connection = $this->em->getConnection();
            $connection->beginTransaction();

            try {
                $this->em->flush();

                $this->mailerService->sendTemplate(
                    to: $user->getEmail(),
                    subject: 'Email verification',
                    template: 'mailer/email_confirmation.html.twig',
                    context: ['user' => $user]
                );

                $connection->commit();
            } catch (Throwable $e) {
                $connection->rollBack();
                throw $e;
            }
        }

        return new ChangeMySettingsResult(
            message: 'Your settings updated successfully.'
        );
    }
}
