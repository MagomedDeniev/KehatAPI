<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\Rules\UserRules;
use App\Domain\ValueObject\Username;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UsernameTest extends TestCase
{
    public function testItTrimsValidUsername(): void
    {
        $username = new Username('  User.Name_1  ');

        self::assertSame('User.Name_1', (string) $username);
    }

    #[DataProvider('invalidUsernamesProvider')]
    public function testItRejectsInvalidUsername(string $value, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Username($value);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function invalidUsernamesProvider(): iterable
    {
        yield 'too short' => ['user', sprintf('Username length must be between %d and %d characters.', UserRules::USERNAME_MIN, UserRules::USERNAME_MAX)];
        yield 'too long' => [str_repeat('a', 181), sprintf('Username length must be between %d and %d characters.', UserRules::USERNAME_MIN, UserRules::USERNAME_MAX)];
        yield 'invalid symbols' => ['bad user!', 'Username can contain only letters, numbers, dots, and underscores.'];
    }
}
