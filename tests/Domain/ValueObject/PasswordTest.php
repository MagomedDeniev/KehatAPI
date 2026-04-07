<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\Rules\UserRules;
use App\Domain\ValueObject\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{
    public function testItAcceptsValidPassword(): void
    {
        $password = new Password('12345678');

        self::assertSame('12345678', (string) $password);
    }

    #[DataProvider('invalidPasswordsProvider')]
    public function testItRejectsInvalidPassword(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Password length must be between %d and %d characters.', UserRules::PASSWORD_MIN, UserRules::PASSWORD_MAX));

        new Password($value);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidPasswordsProvider(): iterable
    {
        yield 'too short' => ['1234567'];
        yield 'too long' => [str_repeat('a', 4097)];
    }
}
