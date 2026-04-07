<?php

declare(strict_types=1);

namespace App\Tests\Application\Account\ChangeMyPassword;

use App\Application\Account\ChangeMyPassword\ChangeMyPasswordCommand;
use App\Application\Account\ChangeMyPassword\ChangeMyPasswordHandler;
use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\TestCase;

final class ChangeMyPasswordHandlerTest extends TestCase
{
    public function testItRejectsInvalidNewPasswordBeforeRepositoryLookup(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $handler = new ChangeMyPasswordHandler($repository, $passwordHasher);

        $repository->expects($this->never())->method('findUserBy');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password length must be between 8 and 4096 characters.');

        $handler(new ChangeMyPasswordCommand(1, 'current-password', 'short'));
    }

    public function testItRejectsMissingUser(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $handler = new ChangeMyPasswordHandler($repository, $passwordHasher);

        $repository->expects($this->once())->method('findUserBy')->with(['id' => 123])->willReturn(null);
        $passwordHasher->expects($this->never())->method('verify');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User not found.');

        $handler(new ChangeMyPasswordCommand(123, 'current-password', '12345678'));
    }

    public function testItRejectsWrongCurrentPassword(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $handler = new ChangeMyPasswordHandler($repository, $passwordHasher);

        $repository->expects($this->once())->method('findUserBy')->with(['id' => 1])->willReturn(UserFactory::domainUser(password: 'hashed-password'));
        $passwordHasher->expects($this->once())->method('verify')->with('hashed-password', 'wrong-password')->willReturn(false);
        $passwordHasher->expects($this->never())->method('hash');
        $repository->expects($this->never())->method('updateDomainUser');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Current password is incorrect.');

        $handler(new ChangeMyPasswordCommand(1, 'wrong-password', '12345678'));
    }

    public function testItChangesPasswordAndClearsPasswordResetState(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $handler = new ChangeMyPasswordHandler($repository, $passwordHasher);

        $user = UserFactory::domainUser(
            id: 1,
            password: 'hashed-password',
            passwordToken: 'reset-token',
            passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
        );

        $repository->expects($this->once())->method('findUserBy')->with(['id' => 1])->willReturn($user);
        $passwordHasher->expects($this->once())->method('verify')->with('hashed-password', 'current-password')->willReturn(true);
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

        $result = $handler(new ChangeMyPasswordCommand(1, 'current-password', '12345678'));

        self::assertSame('Password updated successfully', $result->message);
    }
}
