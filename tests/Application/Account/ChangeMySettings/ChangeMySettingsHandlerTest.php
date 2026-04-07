<?php

declare(strict_types=1);

namespace App\Tests\Application\Account\ChangeMySettings;

use App\Application\Account\ChangeMySettings\ChangeMySettingsCommand;
use App\Application\Account\ChangeMySettings\ChangeMySettingsHandler;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Infrastructure\Service\MailerService;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final class ChangeMySettingsHandlerTest extends TestCase
{
    public function testItRejectsMissingUser(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $handler = new ChangeMySettingsHandler(
            $repository,
            $this->createMock(TokenGeneratorInterface::class),
            new MailerService($this->createMock(MailerInterface::class), $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $repository->expects($this->once())->method('findUserBy')->with(['id' => 99])->willReturn(null);
        $repository->expects($this->never())->method('updateDomainUser');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User not found.');

        $handler(new ChangeMySettingsCommand(99, 'username', 'user@example.com'));
    }

    public function testItRejectsDuplicateEmailOwnedByAnotherUser(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $handler = new ChangeMySettingsHandler(
            $repository,
            $this->createMock(TokenGeneratorInterface::class),
            new MailerService($this->createMock(MailerInterface::class), $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $currentUser = UserFactory::domainUser(id: 10);
        $otherUser = UserFactory::domainUser(id: 11, email: 'new@example.com');
        $calls = 0;

        $repository
            ->expects($this->exactly(2))
            ->method('findUserBy')
            ->willReturnCallback(static function (array $criteria) use (&$calls, $currentUser, $otherUser): ?DomainUser {
                ++$calls;

                return match ($calls) {
                    1 => $currentUser,
                    2 => $otherUser,
                    default => null,
                };
            });

        $repository->expects($this->never())->method('updateDomainUser');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('There is already an account with this email.');

        $handler(new ChangeMySettingsCommand(10, 'username', 'new@example.com'));
    }

    public function testItRejectsDuplicateUsernameOwnedByAnotherUser(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $handler = new ChangeMySettingsHandler(
            $repository,
            $this->createMock(TokenGeneratorInterface::class),
            new MailerService($this->createMock(MailerInterface::class), $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $currentUser = UserFactory::domainUser(id: 10);
        $otherUser = UserFactory::domainUser(id: 11, username: 'new_name');
        $calls = 0;

        $repository
            ->expects($this->exactly(3))
            ->method('findUserBy')
            ->willReturnCallback(static function (array $criteria) use (&$calls, $currentUser, $otherUser): ?DomainUser {
                ++$calls;

                return match ($calls) {
                    1 => $currentUser,
                    2 => null,
                    3 => $otherUser,
                    default => null,
                };
            });

        $repository->expects($this->never())->method('updateDomainUser');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('There is already an account with this username.');

        $handler(new ChangeMySettingsCommand(10, 'new_name', 'user@example.com'));
    }

    public function testItAllowsSameCredentialsForCurrentUserWithoutSendingEmail(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);

        $handler = new ChangeMySettingsHandler(
            $repository,
            $tokenGenerator,
            new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $currentUser = UserFactory::domainUser(id: 10, email: 'user@example.com', confirmedEmail: 'user@example.com', username: 'old_name');
        $calls = 0;

        $repository
            ->expects($this->exactly(3))
            ->method('findUserBy')
            ->willReturnCallback(static function (array $criteria) use (&$calls, $currentUser): ?DomainUser {
                ++$calls;

                return match ($calls) {
                    1, 2, 3 => $currentUser,
                    default => null,
                };
            });

        $tokenGenerator->expects($this->never())->method('generateToken');
        $mailer->expects($this->never())->method('send');

        $repository
            ->expects($this->once())
            ->method('updateDomainUser')
            ->with($this->callback(static function (DomainUser $updatedUser): bool {
                self::assertSame('new_name', $updatedUser->getUsername());
                self::assertSame('user@example.com', $updatedUser->getEmail());
                self::assertNull($updatedUser->getEmailToken());

                return true;
            }))
            ->willReturnCallback(static fn (DomainUser $updatedUser): DomainUser => $updatedUser);

        $result = $handler(new ChangeMySettingsCommand(10, 'new_name', 'user@example.com'));

        self::assertSame('Your settings updated successfully.', $result->message);
    }

    public function testItUpdatesEmailAndSendsNewConfirmation(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);

        $handler = new ChangeMySettingsHandler(
            $repository,
            $tokenGenerator,
            new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $currentUser = UserFactory::domainUser(
            id: 10,
            email: 'old@example.com',
            confirmedEmail: 'old@example.com',
            username: 'old_name',
            emailToken: null,
            emailTokenExpiresAt: null,
        );

        $calls = 0;
        $repository
            ->expects($this->exactly(3))
            ->method('findUserBy')
            ->willReturnCallback(static function (array $criteria) use (&$calls, $currentUser): ?DomainUser {
                ++$calls;

                return match ($calls) {
                    1 => $currentUser,
                    2, 3 => null,
                    default => null,
                };
            });

        $tokenGenerator->expects($this->once())->method('generateToken')->willReturn('email-token');

        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (TemplatedEmail $email): bool {
                self::assertSame('new@example.com', $email->getTo()[0]->getAddress());
                self::assertSame('Email verification', $email->getSubject());
                self::assertSame('mailer/email_confirmation.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        $repository
            ->expects($this->once())
            ->method('updateDomainUser')
            ->with($this->callback(static function (DomainUser $updatedUser): bool {
                self::assertSame('new_name', $updatedUser->getUsername());
                self::assertSame('new@example.com', $updatedUser->getEmail());
                self::assertSame('old@example.com', $updatedUser->getConfirmedEmail());
                self::assertSame('email-token', $updatedUser->getEmailToken());
                self::assertGreaterThan(time(), $updatedUser->getEmailTokenExpiresAt()?->getTimestamp() ?? 0);

                return true;
            }))
            ->willReturnCallback(static fn (DomainUser $updatedUser): DomainUser => $updatedUser);

        $result = $handler(new ChangeMySettingsCommand(10, 'new_name', 'new@example.com'));

        self::assertSame('Your settings updated successfully.', $result->message);
    }

    public function testItRejectsInvalidEmailBeforeRepositoryLookup(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);

        $handler = new ChangeMySettingsHandler(
            $repository,
            $this->createMock(TokenGeneratorInterface::class),
            new MailerService($this->createMock(MailerInterface::class), $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $repository->expects($this->never())->method('findUserBy');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is not valid.');

        $handler(new ChangeMySettingsCommand(10, 'username', 'bad-email'));
    }
}
