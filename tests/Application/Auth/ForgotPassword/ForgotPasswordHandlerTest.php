<?php

declare(strict_types=1);

namespace App\Tests\Application\Auth\ForgotPassword;

use App\Application\Auth\ForgotPassword\ForgotPasswordCommand;
use App\Application\Auth\ForgotPassword\ForgotPasswordHandler;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Infrastructure\Service\MailerService;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final class ForgotPasswordHandlerTest extends TestCase
{
    public function testItAssignsResetTokenAndSendsRecoveryEmailForExistingUser(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $handler = new ForgotPasswordHandler(
            $repository,
            $tokenGenerator,
            new MailerService($mailer, $logger, 'no-reply@example.com', 'Kehat'),
        );

        $user = UserFactory::domainUser(passwordToken: null, passwordTokenExpiresAt: null);

        $repository
            ->expects($this->once())
            ->method('findUserBy')
            ->with(['email' => 'test.user@example.com'])
            ->willReturn($user);

        $tokenGenerator->expects($this->once())->method('generateToken')->willReturn(UserFactory::VALID_PASSWORD_TOKEN);

        $repository
            ->expects($this->once())
            ->method('updateDomainUser')
            ->with($this->callback(static function (DomainUser $updatedUser): bool {
                self::assertSame(UserFactory::VALID_PASSWORD_TOKEN, $updatedUser->getPasswordToken());
                self::assertGreaterThan(time(), $updatedUser->getPasswordTokenExpiresAt()?->getTimestamp() ?? 0);

                return true;
            }))
            ->willReturnCallback(static fn (DomainUser $updatedUser): DomainUser => $updatedUser);

        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (TemplatedEmail $email): bool {
                self::assertSame('user@example.com', $email->getTo()[0]->getAddress());
                self::assertSame('Account recovery', $email->getSubject());
                self::assertSame('mailer/recovery_password.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        $logger->expects($this->never())->method('error');

        $result = $handler(new ForgotPasswordCommand(' Test.User@example.com '));

        self::assertSame('test.user@example.com', $result->email);
        self::assertSame('If email is valid, you will receive a link to reset your password.', $result->message);
    }

    public function testItReturnsGenericResponseWhenUserDoesNotExist(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);

        $handler = new ForgotPasswordHandler(
            $repository,
            $tokenGenerator,
            new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $repository->expects($this->once())->method('findUserBy')->with(['email' => 'user@example.com'])->willReturn(null);
        $tokenGenerator->expects($this->never())->method('generateToken');
        $repository->expects($this->never())->method('updateDomainUser');
        $mailer->expects($this->never())->method('send');

        $result = $handler(new ForgotPasswordCommand('user@example.com'));

        self::assertSame('user@example.com', $result->email);
        self::assertSame('If email is valid, you will receive a link to reset your password.', $result->message);
    }

    public function testItRejectsInvalidEmail(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);

        $handler = new ForgotPasswordHandler(
            $repository,
            $this->createMock(TokenGeneratorInterface::class),
            new MailerService($this->createMock(MailerInterface::class), $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
        );

        $repository->expects($this->never())->method('findUserBy');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is not valid.');

        $handler(new ForgotPasswordCommand('bad-email'));
    }
}
