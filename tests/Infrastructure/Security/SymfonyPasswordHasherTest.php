<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Security\SymfonyPasswordHasher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SymfonyPasswordHasherTest extends TestCase
{
    public function testHashDelegatesToSymfonyHasherUsingUserEntity(): void
    {
        $innerHasher = $this->createMock(UserPasswordHasherInterface::class);
        $service = new SymfonyPasswordHasher($innerHasher);

        $innerHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with(
                $this->callback(static fn (User $user): bool => $user instanceof User),
                'plain-password',
            )
            ->willReturn('hashed-password');

        self::assertSame('hashed-password', $service->hash('plain-password'));
    }

    public function testVerifyDelegatesToSymfonyHasherUsingPreparedUserEntity(): void
    {
        $innerHasher = $this->createMock(UserPasswordHasherInterface::class);
        $service = new SymfonyPasswordHasher($innerHasher);

        $innerHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with(
                $this->callback(static function (User $user): bool {
                    self::assertSame('stored-hash', $user->getPassword());

                    return true;
                }),
                'plain-password',
            )
            ->willReturn(true);

        self::assertTrue($service->verify('stored-hash', 'plain-password'));
    }
}
