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
        $email = (string) (new Email($command->email));
        $username = (string) (new Username($command->username));

        $user = $this->domainUserRepository->findUserBy(['id' => $command->userId]);

        if (!$user instanceof DomainUser) {
            throw new \DomainException('User not found.');
        }

        $userByEmail = $this->domainUserRepository->findUserBy(['email' => $email]);
        if ($userByEmail instanceof DomainUser && $userByEmail->getId() !== $user->getId()) {
            throw new \DomainException('There is already an account with this email.');
        }

        $userByUsername = $this->domainUserRepository->findUserBy(['username' => $username]);
        if ($userByUsername instanceof DomainUser && $userByUsername->getId() !== $user->getId()) {
            throw new \DomainException('There is already an account with this username.');
        }

        // Логика email такая, что подтвержденной почта считается если email = confirmedEmail
        // При изменении email, email должен сразу меняться, тем самым давая понять что email != confirmedEmail
        if ($email === $user->getConfirmedEmail()) {
            $user->saveSettings($username, $email);
        } else {
            $tokenExpiresAt = new TokenExpirationTime();
            $user->saveSettingsWithEmailUpdate($username, $email, $this->tokenGenerator->generateToken(), $tokenExpiresAt->value());

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
