<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\Rules\UserRules;
use App\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testItNormalizesValidEmail(): void
    {
        $email = new Email('  TeSt.User@Example.COM ');

        self::assertSame('test.user@example.com', (string) $email);
    }

    #[DataProvider('invalidEmailsProvider')]
    public function testItRejectsInvalidEmails(string $value, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Email($value);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function invalidEmailsProvider(): iterable
    {
        $tooLongButValid = 'a@'
            .str_repeat('a', 63).'.'
            .str_repeat('b', 63).'.'
            .str_repeat('c', 47).'.com';

        yield 'blank after trim' => ['   ', 'Email is not valid.'];
        yield 'wrong format' => ['not-an-email', 'Email is not valid.'];
        yield 'too short' => ['a@b.c', sprintf('Email length must be between %d and %d characters.', UserRules::EMAIL_MIN, UserRules::EMAIL_MAX)];
        yield 'too long' => [$tooLongButValid, sprintf('Email length must be between %d and %d characters.', UserRules::EMAIL_MIN, UserRules::EMAIL_MAX)];
    }
}
