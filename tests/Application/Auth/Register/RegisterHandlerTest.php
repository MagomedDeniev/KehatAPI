<?php

declare(strict_types=1);

namespace App\Tests\Application\Auth\Register;

use App\Application\Auth\Register\RegisterCommand;
use App\Application\Auth\Register\RegisterHandler;
use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Infrastructure\Service\MailerService;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final class RegisterHandlerTest extends TestCase
{
    public function testItRegistersNewUserAndSendsConfirmationEmail(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $handler = new RegisterHandler(
            $repository,
            $passwordHasher,
            $tokenGenerator,
            new MailerService($mailer, $logger, 'no-reply@example.com', 'Kehat'),
        );

        $lookupCalls = [];
        $repository
            ->expects($this->exactly(2))
            ->method('findUserBy')
            ->willReturnCallback(static function (array $criteria) use (&$lookupCalls): ?DomainUser {
                $lookupCalls[] = $criteria;

                return null;
            });

        $passwordHasher
            ->expects($this->once())
            ->method('hash')
            ->with('12345678')
            ->willReturn(UserFactory::VALID_PASSWORD_HASH);

        $tokenGenerator
            ->expects($this->once())
            ->method('generateToken')
            ->willReturn(UserFactory::VALID_EMAIL_TOKEN);

        $repository
            ->expects($this->once())
            ->method('createDomainUser')
            ->willReturnCallback(static function (DomainUser $user): DomainUser {
                self::assertNull($user->getId());
                self::assertSame('test.user@example.com', $user->getEmail());
                self::assertSame('Test_User', $user->getUsername());
                self::assertSame(UserFactory::VALID_PASSWORD_HASH, $user->getPassword());
                self::assertSame(UserFactory::VALID_EMAIL_TOKEN, $user->getEmailToken());
                self::assertNull($user->getConfirmedEmail());
                self::assertGreaterThan(time(), $user->getEmailTokenExpiresAt()?->getTimestamp() ?? 0);

                return UserFactory::domainUser(
                    id: 42,
                    email: $user->getEmail(),
                    confirmedEmail: $user->getConfirmedEmail(),
                    password: $user->getPassword(),
                    username: $user->getUsername(),
                    roles: $user->getRoles(),
                    passwordToken: $user->getPasswordToken(),
                    passwordTokenExpiresAt: $user->getPasswordTokenExpiresAt(),
                    emailToken: $user->getEmailToken(),
                    emailTokenExpiresAt: $user->getEmailTokenExpiresAt(),
                    registeredAt: $user->getRegisteredAt(),
                );
            });

        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (TemplatedEmail $email): bool {
                self::assertSame('no-reply@example.com', $email->getFrom()[0]->getAddress());
                self::assertSame('Kehat', $email->getFrom()[0]->getName());
                self::assertSame('test.user@example.com', $email->getTo()[0]->getAddress());
                self::assertSame('Welcome message', $email->getSubject());
                self::assertSame('mailer/registration.html.twig', $email->getHtmlTemplate());
                self::assertInstanceOf(DomainUser::class, $email->getContext()['user']);

                return true;
            }));

        $logger->expects($this->never())->method('error');

        $result = $handler(new RegisterCommand('  Test.User@example.com ', ' Test_User ', '12345678'));

        self::assertSame([['email' => 'test.user@example.com'], ['username' => 'Test_User']], $lookupCalls);
        self::assertSame(42, $result->userId);
        self::assertSame('test.user@example.com', $result->email);
        self::assertSame('User successfully registered, check your email for further instructions.', $result->message);
    }

    public function testItRejectsExistingEmail(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);

        $handler = new RegisterHandler(
            $repository,
            $passwordHasher,
            $tokenGenerator,
            new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $repository
            ->expects($this->once())
            ->method('findUserBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn(UserFactory::domainUser());

        $passwordHasher->expects($this->never())->method('hash');
        $tokenGenerator->expects($this->never())->method('generateToken');
        $repository->expects($this->never())->method('createDomainUser');
        $mailer->expects($this->never())->method('send');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('There is already an account with this email.');

        $handler(new RegisterCommand('user@example.com', 'username', '12345678'));
    }

    public function testItRejectsExistingUsername(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);

        $handler = new RegisterHandler(
            $repository,
            $passwordHasher,
            $tokenGenerator,
            new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $calls = 0;
        $repository
            ->expects($this->exactly(2))
            ->method('findUserBy')
            ->willReturnCallback(static function (array $criteria) use (&$calls): ?DomainUser {
                ++$calls;
                self::assertSame(1 === $calls ? ['email' => 'user@example.com'] : ['username' => 'username'], $criteria);

                return 2 === $calls ? UserFactory::domainUser(username: 'username') : null;
            });

        $passwordHasher->expects($this->never())->method('hash');
        $tokenGenerator->expects($this->never())->method('generateToken');
        $repository->expects($this->never())->method('createDomainUser');
        $mailer->expects($this->never())->method('send');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('There is already an account with this username.');

        $handler(new RegisterCommand('user@example.com', 'username', '12345678'));
    }

    public function testItRejectsInvalidEmailBeforeRepositoryLookup(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);

        $handler = new RegisterHandler(
            $repository,
            $this->createMock(PasswordHasherInterface::class),
            $this->createMock(TokenGeneratorInterface::class),
            new MailerService($this->createMock(MailerInterface::class), $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $repository->expects($this->never())->method('findUserBy');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is not valid.');

        $handler(new RegisterCommand('bad-email', 'username', '12345678'));
    }

    public function testItRejectsInvalidPasswordAfterUniquenessChecks(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);

        $handler = new RegisterHandler(
            $repository,
            $passwordHasher,
            $tokenGenerator,
            new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $repository->expects($this->exactly(2))->method('findUserBy')->willReturn(null);
        $passwordHasher->expects($this->never())->method('hash');
        $tokenGenerator->expects($this->never())->method('generateToken');
        $repository->expects($this->never())->method('createDomainUser');
        $mailer->expects($this->never())->method('send');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password length must be between 8 and 4096 characters.');

        $handler(new RegisterCommand('user@example.com', 'username', 'short'));
    }

    public function testItThrowsWhenPersistedUserHasNoId(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);

        $handler = new RegisterHandler(
            $repository,
            $passwordHasher,
            $tokenGenerator,
            new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $repository->expects($this->exactly(2))->method('findUserBy')->willReturn(null);
        $passwordHasher->expects($this->once())->method('hash')->willReturn(UserFactory::VALID_PASSWORD_HASH);
        $tokenGenerator->expects($this->once())->method('generateToken')->willReturn(UserFactory::VALID_EMAIL_TOKEN);
        $repository->expects($this->once())->method('createDomainUser')->willReturn(UserFactory::domainUser(
            id: null,
            confirmedEmail: null,
            emailToken: UserFactory::VALID_EMAIL_TOKEN,
            emailTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
        ));
        $mailer->expects($this->once())->method('send');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Registered user must have id.');

        $handler(new RegisterCommand('user@example.com', 'username', '12345678'));
    }
}
