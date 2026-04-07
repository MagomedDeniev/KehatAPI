<?php

declare(strict_types=1);

namespace App\Tests\Application\Auth\RestorePassword;

use App\Application\Auth\RestorePassword\RestorePasswordCommand;
use App\Application\Auth\RestorePassword\RestorePasswordHandler;
use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\TestCase;

final class RestorePasswordHandlerTest extends TestCase
{
    public function testItRejectsUnknownResetToken(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $handler = new RestorePasswordHandler($passwordHasher, $repository);

        $repository->expects($this->once())->method('findUserBy')->with(['passwordToken' => 'missing-token'])->willReturn(null);
        $passwordHasher->expects($this->never())->method('hash');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid password reset token.');

        $handler(new RestorePasswordCommand('missing-token', '12345678'));
    }

    public function testItRejectsExpiredResetToken(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $handler = new RestorePasswordHandler($passwordHasher, $repository);

        $repository
            ->expects($this->once())
            ->method('findUserBy')
            ->with(['passwordToken' => 'expired-token'])
            ->willReturn(UserFactory::domainUser(
                passwordToken: 'expired-token',
                passwordTokenExpiresAt: new \DateTimeImmutable('-1 hour'),
            ));

        $passwordHasher->expects($this->never())->method('hash');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Password reset token is invalid or expired.');

        $handler(new RestorePasswordCommand('expired-token', '12345678'));
    }

    public function testItRejectsInvalidNewPassword(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $handler = new RestorePasswordHandler($passwordHasher, $repository);

        $repository
            ->expects($this->once())
            ->method('findUserBy')
            ->with(['passwordToken' => 'valid-token'])
            ->willReturn(UserFactory::domainUser(
                passwordToken: 'valid-token',
                passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
            ));

        $passwordHasher->expects($this->never())->method('hash');
        $repository->expects($this->never())->method('updateDomainUser');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password length must be between 8 and 4096 characters.');

        $handler(new RestorePasswordCommand('valid-token', 'short'));
    }

    public function testItRestoresPasswordAndClearsResetToken(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $handler = new RestorePasswordHandler($passwordHasher, $repository);

        $user = UserFactory::domainUser(
            id: 15,
            password: 'old-password',
            passwordToken: 'valid-token',
            passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
        );

        $repository->expects($this->once())->method('findUserBy')->with(['passwordToken' => 'valid-token'])->willReturn($user);
        $passwordHasher->expects($this->once())->method('hash')->with('12345678')->willReturn('new-hashed-password');
        $repository
            ->expects($this->once())
            ->method('updateDomainUser')
            ->with($this->callback(static function (DomainUser $updatedUser): bool {
                self::assertSame('new-hashed-password', $updatedUser->getPassword());
                self::assertNull($updatedUser->getPasswordToken());
                self::assertNull($updatedUser->getPasswordTokenExpiresAt());

                return true;
            }))
            ->willReturnCallback(static fn (DomainUser $updatedUser): DomainUser => $updatedUser);

        $result = $handler(new RestorePasswordCommand('valid-token', '12345678'));

        self::assertSame(15, $result->userId);
        self::assertSame('Your password has been restored, you can login now.', $result->message);
    }

    public function testItThrowsWhenUserHasNoId(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $handler = new RestorePasswordHandler($passwordHasher, $repository);

        $repository->expects($this->once())->method('findUserBy')->willReturn(UserFactory::domainUser(
            id: null,
            confirmedEmail: null,
            passwordToken: 'valid-token',
            passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
        ));
        $passwordHasher->expects($this->once())->method('hash')->willReturn('new-hashed-password');
        $repository->expects($this->once())->method('updateDomainUser')->willReturnCallback(static fn (DomainUser $updatedUser): DomainUser => $updatedUser);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Registered user must have id.');

        $handler(new RestorePasswordCommand('valid-token', '12345678'));
    }
}
