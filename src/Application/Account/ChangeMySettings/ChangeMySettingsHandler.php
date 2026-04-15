<?php

declare(strict_types=1);

namespace App\Application\Account\ChangeMySettings;

use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EmailToken;
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

        $user = $this->domainUserRepository->findUserById($command->userId);

        if (!$user instanceof DomainUser) {
            throw new \DomainException('User not found.');
        }

        $userByEmail = $this->domainUserRepository->findUserByEmail((string) $email);
        if ($userByEmail instanceof DomainUser && $userByEmail->getId() !== $user->getId()) {
            throw new \DomainException('There is already an account with this email.');
        }

        $userByUsername = $this->domainUserRepository->findUserByUsername((string) $username);
        if ($userByUsername instanceof DomainUser && $userByUsername->getId() !== $user->getId()) {
            throw new \DomainException('There is already an account with this username.');
        }

        // Email может быть и не подтвержденным (не путать с confirmedEmail), поэтому проверяем меняется ли Email
        // Это нужно знать как минимум для выдачи нового токена на фронте, в случае если почта изменена
        $emailUpdated = (string) $email !== $user->getEmail();

        // Логика email такая, что подтвержденной почта считается если email = confirmedEmail
        // При изменении email, email должен сразу меняться, тем самым давая понять что email != confirmedEmail
        if ((string) $email === $user->getConfirmedEmail()) {
            $user->saveSettings($username, $email, $command->gender, $command->birthDate);
        } else {
            $token = new EmailToken($this->tokenGenerator->generateToken());
            $tokenExpiresAt = new TokenExpirationTime();
            $user->saveSettingsWithEmailUpdate($username, $email, $command->gender, $command->birthDate, $token, $tokenExpiresAt);

            $this->mailerService->sendTemplate(
                to: $user->getEmail(),
                subject: 'Email verification',
                template: 'mailer/email_confirmation.html.twig',
                context: ['user' => $user]
            );
        }

        $this->domainUserRepository->updateDomainUser($user);

        return new ChangeMySettingsResult(
            message: 'Your settings updated successfully.',
            emailUpdated: $emailUpdated
        );
    }
}
