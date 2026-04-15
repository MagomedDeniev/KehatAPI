<?php

declare(strict_types=1);

namespace App\Tests\Application\Account\ChangeMySettings;

use App\Application\Account\ChangeMySettings\ChangeMySettingsCommand;
use App\Application\Account\ChangeMySettings\ChangeMySettingsHandler;
use App\Domain\Entity\DomainUser;
use App\Domain\Enum\GenderEnum;
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
    private const BIRTH_DATE = '1990-05-20';

    public function testItRejectsMissingUser(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $handler = new ChangeMySettingsHandler(
            $repository,
            $this->createMock(TokenGeneratorInterface::class),
            new MailerService($this->createMock(MailerInterface::class), $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $repository->expects($this->once())->method('findUserById')->with(99)->willReturn(null);
        $repository->expects($this->never())->method('updateDomainUser');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User not found.');

        $handler(new ChangeMySettingsCommand(99, 'username', GenderEnum::MALE, new \DateTimeImmutable(self::BIRTH_DATE), 'user@example.com'));
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

        $repository->expects($this->once())->method('findUserById')->with(10)->willReturn($currentUser);
        $repository->expects($this->once())->method('findUserByEmail')->with('new@example.com')->willReturn($otherUser);
        $repository->expects($this->never())->method('findUserByUsername');

        $repository->expects($this->never())->method('updateDomainUser');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('There is already an account with this email.');

        $handler(new ChangeMySettingsCommand(10, 'username', GenderEnum::MALE, new \DateTimeImmutable(self::BIRTH_DATE), 'new@example.com'));
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
        $repository->expects($this->once())->method('findUserById')->with(10)->willReturn($currentUser);
        $repository->expects($this->once())->method('findUserByEmail')->with('user@example.com')->willReturn(null);
        $repository->expects($this->once())->method('findUserByUsername')->with('new_name')->willReturn($otherUser);

        $repository->expects($this->never())->method('updateDomainUser');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('There is already an account with this username.');

        $handler(new ChangeMySettingsCommand(10, 'new_name', GenderEnum::MALE, new \DateTimeImmutable(self::BIRTH_DATE), 'user@example.com'));
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
        $repository->expects($this->once())->method('findUserById')->with(10)->willReturn($currentUser);
        $repository->expects($this->once())->method('findUserByEmail')->with('user@example.com')->willReturn($currentUser);
        $repository->expects($this->once())->method('findUserByUsername')->with('new_name')->willReturn($currentUser);

        $tokenGenerator->expects($this->never())->method('generateToken');
        $mailer->expects($this->never())->method('send');

        $repository
            ->expects($this->once())
            ->method('updateDomainUser')
            ->with($this->callback(static function (DomainUser $updatedUser): bool {
                self::assertSame('new_name', $updatedUser->getUsername());
                self::assertSame('user@example.com', $updatedUser->getEmail());
                self::assertSame(GenderEnum::FEMALE, $updatedUser->getGender());
                self::assertSame(self::BIRTH_DATE, $updatedUser->getBirthDate()->format('Y-m-d'));
                self::assertNull($updatedUser->getEmailToken());

                return true;
            }))
            ->willReturnCallback(static fn (DomainUser $updatedUser): DomainUser => $updatedUser);

        $result = $handler(new ChangeMySettingsCommand(10, 'new_name', GenderEnum::FEMALE, new \DateTimeImmutable(self::BIRTH_DATE), 'user@example.com'));

        self::assertSame('Your settings updated successfully.', $result->message);
        self::assertFalse($result->emailUpdated);
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

        $repository->expects($this->once())->method('findUserById')->with(10)->willReturn($currentUser);
        $repository->expects($this->once())->method('findUserByEmail')->with('new@example.com')->willReturn(null);
        $repository->expects($this->once())->method('findUserByUsername')->with('new_name')->willReturn(null);

        $tokenGenerator->expects($this->once())->method('generateToken')->willReturn(UserFactory::VALID_EMAIL_TOKEN);

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
                self::assertSame(GenderEnum::FEMALE, $updatedUser->getGender());
                self::assertSame(self::BIRTH_DATE, $updatedUser->getBirthDate()->format('Y-m-d'));
                self::assertSame(UserFactory::VALID_EMAIL_TOKEN, $updatedUser->getEmailToken());
                self::assertGreaterThan(time(), $updatedUser->getEmailTokenExpiresAt()?->getTimestamp() ?? 0);

                return true;
            }))
            ->willReturnCallback(static fn (DomainUser $updatedUser): DomainUser => $updatedUser);

        $result = $handler(new ChangeMySettingsCommand(10, 'new_name', GenderEnum::FEMALE, new \DateTimeImmutable(self::BIRTH_DATE), 'new@example.com'));

        self::assertSame('Your settings updated successfully.', $result->message);
        self::assertTrue($result->emailUpdated);
    }

    public function testItRejectsInvalidEmailBeforeRepositoryLookup(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);

        $handler = new ChangeMySettingsHandler(
            $repository,
            $this->createMock(TokenGeneratorInterface::class),
            new MailerService($this->createMock(MailerInterface::class), $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $repository->expects($this->never())->method('findUserById');
        $repository->expects($this->never())->method('findUserByEmail');
        $repository->expects($this->never())->method('findUserByUsername');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is not valid.');

        $handler(new ChangeMySettingsCommand(10, 'username', GenderEnum::MALE, new \DateTimeImmutable(self::BIRTH_DATE), 'bad-email'));
    }
}
