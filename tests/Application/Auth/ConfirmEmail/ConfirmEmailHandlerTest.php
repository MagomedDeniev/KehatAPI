<?php

declare(strict_types=1);

namespace App\Tests\Application\Auth\ConfirmEmail;

use App\Application\Auth\ConfirmEmail\ConfirmEmailCommand;
use App\Application\Auth\ConfirmEmail\ConfirmEmailHandler;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\TestCase;

final class ConfirmEmailHandlerTest extends TestCase
{
    public function testItRejectsUnknownEmailToken(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $handler = new ConfirmEmailHandler($repository);

        $repository->expects($this->once())->method('findUserByEmailToken')->with('missing-token')->willReturn(null);
        $repository->expects($this->never())->method('updateDomainUser');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid email confirmation token.');

        $handler(new ConfirmEmailCommand('missing-token'));
    }

    public function testItRejectsExpiredEmailToken(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $handler = new ConfirmEmailHandler($repository);

        $repository
            ->expects($this->once())
            ->method('findUserByEmailToken')
            ->with('expired-token')
            ->willReturn(UserFactory::domainUser(
                confirmedEmail: null,
                emailToken: 'expired-token',
                emailTokenExpiresAt: new \DateTimeImmutable('-1 hour'),
            ));

        $repository->expects($this->never())->method('updateDomainUser');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email confirmation token is invalid or expired.');

        $handler(new ConfirmEmailCommand('expired-token'));
    }

    public function testItConfirmsEmailAndClearsConfirmationToken(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $handler = new ConfirmEmailHandler($repository);

        $user = UserFactory::domainUser(
            confirmedEmail: null,
            emailToken: 'valid-token',
            emailTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
        );

        $repository->expects($this->once())->method('findUserByEmailToken')->with('valid-token')->willReturn($user);
        $repository
            ->expects($this->once())
            ->method('updateDomainUser')
            ->with($this->callback(static function (DomainUser $updatedUser): bool {
                self::assertSame('user@example.com', $updatedUser->getConfirmedEmail());
                self::assertNull($updatedUser->getEmailToken());
                self::assertNull($updatedUser->getEmailTokenExpiresAt());

                return true;
            }))
            ->willReturnCallback(static fn (DomainUser $updatedUser): DomainUser => $updatedUser);

        $result = $handler(new ConfirmEmailCommand('valid-token'));

        self::assertSame('user@example.com', $result->email);
        self::assertSame('Your email has been verified.', $result->message);
    }
}
