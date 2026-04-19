<?php

declare(strict_types=1);

namespace App\Api\Account\SettingsChange;

use App\Doctrine\Entity\User;
use App\Doctrine\Repository\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EmailToken;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Domain\ValueObject\Username;
use App\Service\MailerService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final readonly class SettingsChangeHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenGeneratorInterface $tokenGenerator,
        private MailerService $mailerService,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SettingsChangeCommand $command): SettingsChangeResult
    {
        $email = new Email($command->email);
        $username = new Username($command->username);

        $user = $this->userRepository->findUserById($command->userId);

        if (!$user instanceof User) {
            throw new \DomainException('User not found.');
        }

        $userByEmail = $this->userRepository->findUserByEmail((string) $email);
        if ($userByEmail instanceof User && $userByEmail->getId() !== $user->getId()) {
            throw new \DomainException('There is already an account with this email.');
        }

        $userByUsername = $this->userRepository->findUserByUsername((string) $username);
        if ($userByUsername instanceof User && $userByUsername->getId() !== $user->getId()) {
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

        $this->userRepository->updateUser($user);

        return new SettingsChangeResult(
            message: 'Your settings updated successfully.',
            emailUpdated: $emailUpdated
        );
    }
}
