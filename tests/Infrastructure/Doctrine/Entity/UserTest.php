<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine\Entity;

use App\Infrastructure\Doctrine\Entity\User;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    #[DataProvider('tokenValidationProvider')]
    public function testTokenValidation(
        User $user,
        string $token,
        string $type,
        bool $expected,
    ): void {
        self::assertSame($expected, $user->tokenIsValid($token, $type));
    }

    /**
     * @return iterable<string, array{0: User, 1: string, 2: string, 3: bool}>
     */
    public static function tokenValidationProvider(): iterable
    {
        yield 'valid email token' => [
            UserFactory::ormUser(emailToken: 'email-token', emailTokenExpiresAt: new \DateTimeImmutable('+1 hour')),
            'email-token',
            'email',
            true,
        ];
        yield 'valid password token' => [
            UserFactory::ormUser(passwordToken: 'password-token', passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour')),
            'password-token',
            'password',
            true,
        ];
        yield 'unknown type' => [
            UserFactory::ormUser(emailToken: 'email-token', emailTokenExpiresAt: new \DateTimeImmutable('+1 hour')),
            'email-token',
            'other',
            false,
        ];
        yield 'missing stored token' => [
            UserFactory::ormUser(emailToken: null, emailTokenExpiresAt: new \DateTimeImmutable('+1 hour')),
            'email-token',
            'email',
            false,
        ];
        yield 'empty provided token' => [
            UserFactory::ormUser(emailToken: 'email-token', emailTokenExpiresAt: new \DateTimeImmutable('+1 hour')),
            '',
            'email',
            false,
        ];
        yield 'mismatched token' => [
            UserFactory::ormUser(emailToken: 'stored-token', emailTokenExpiresAt: new \DateTimeImmutable('+1 hour')),
            'other-token',
            'email',
            false,
        ];
        yield 'expired token' => [
            UserFactory::ormUser(emailToken: 'email-token', emailTokenExpiresAt: new \DateTimeImmutable('-1 hour')),
            'email-token',
            'email',
            false,
        ];
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = UserFactory::ormUser(email: 'user@example.com');

        self::assertSame('user@example.com', $user->getUserIdentifier());
    }

    public function testGetUserIdentifierRejectsEmptyEmail(): void
    {
        $user = UserFactory::ormUser(email: '');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('User email cannot be empty.');

        $user->getUserIdentifier();
    }

    public function testGetRolesAlwaysContainsRoleUserOnlyOnce(): void
    {
        $user = UserFactory::ormUser(roles: ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testSerializeReplacesPasswordWithChecksum(): void
    {
        $user = UserFactory::ormUser(password: 'plain-password');

        $data = $user->__serialize();

        self::assertSame(hash('crc32c', 'plain-password'), $data["\0".User::class."\0password"]);
        self::assertNotContains('plain-password', $data);
    }
}
