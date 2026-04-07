<?php

declare(strict_types=1);

namespace App\Application\Account\ChangeMySettings;

use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Domain\ValueObject\Username;
use App\Infrastructure\Service\MailerService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final readonly class ChangeMySettingsHandler
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
    public function __invoke(ChangeMySettingsCommand $command): ChangeMySettingsResult
    {
        $email = new Email($command->email);
        $username = new Username($command->username);

        $user = $this->domainUserRepository->findUserBy(['id' => $command->userId]);

        if (!$user instanceof DomainUser) {
            throw new \DomainException('User not found.');
        }

        if ($email->value() === $user->getConfirmedEmail()) {
            $user->saveSettings($username->value(), $email->value());
        } else {
            $tokenExpiresAt = new TokenExpirationTime();
            $user->saveSettingsWithEmailUpdate($username->value(), $email->value(), $this->tokenGenerator->generateToken(), $tokenExpiresAt->value());

            $this->mailerService->sendTemplate(
                to: $user->getEmail(),
                subject: 'Email verification',
                template: 'mailer/email_confirmation.html.twig',
                context: ['user' => $user]
            );
        }

        $this->domainUserRepository->updateDomainUser($user);

        return new ChangeMySettingsResult(
            message: 'Your settings updated successfully.'
        );
    }
}
