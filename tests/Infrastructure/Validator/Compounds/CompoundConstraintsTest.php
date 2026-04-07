<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Validator\Compounds;

use App\Domain\Rules\UserRules;
use App\Infrastructure\Validator\Compounds\CurrentPassword;
use App\Infrastructure\Validator\Compounds\Email;
use App\Infrastructure\Validator\Compounds\EmailToken;
use App\Infrastructure\Validator\Compounds\Password;
use App\Infrastructure\Validator\Compounds\PasswordToken;
use App\Infrastructure\Validator\Compounds\SavedEmail;
use App\Infrastructure\Validator\Compounds\Username;
use App\Infrastructure\Validator\Constraints\UniqueUserCredentials;
use App\Infrastructure\Validator\Constraints\ValidEmailToken;
use App\Infrastructure\Validator\Constraints\ValidPasswordToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Email as AssertEmail;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class CompoundConstraintsTest extends TestCase
{
    public function testUniqueUserCredentialsTargetsClassLevel(): void
    {
        self::assertSame(UniqueUserCredentials::CLASS_CONSTRAINT, (new UniqueUserCredentials())->getTargets());
    }

    public function testEmailCompoundContainsExpectedConstraints(): void
    {
        $constraints = $this->getConstraints(new Email());

        self::assertCount(3, $constraints);
        self::assertInstanceOf(NotBlank::class, $constraints[0]);
        self::assertInstanceOf(AssertEmail::class, $constraints[1]);
        self::assertInstanceOf(Length::class, $constraints[2]);
        self::assertSame(UserRules::EMAIL_MIN, $constraints[2]->min);
        self::assertSame(UserRules::EMAIL_MAX, $constraints[2]->max);
    }

    public function testSavedEmailCompoundContainsExpectedConstraints(): void
    {
        $constraints = $this->getConstraints(new SavedEmail());

        self::assertCount(2, $constraints);
        self::assertInstanceOf(NotBlank::class, $constraints[0]);
        self::assertInstanceOf(AssertEmail::class, $constraints[1]);
    }

    public function testUsernameCompoundContainsExpectedConstraints(): void
    {
        $constraints = $this->getConstraints(new Username());

        self::assertCount(3, $constraints);
        self::assertInstanceOf(NotBlank::class, $constraints[0]);
        self::assertInstanceOf(Length::class, $constraints[1]);
        self::assertSame(UserRules::USERNAME_MIN, $constraints[1]->min);
        self::assertSame(UserRules::USERNAME_MAX, $constraints[1]->max);
        self::assertInstanceOf(Regex::class, $constraints[2]);
        self::assertSame(UserRules::USERNAME_PATTERN, $constraints[2]->pattern);
        self::assertSame('form.username.can.consist.symbols', $constraints[2]->message);
    }

    public function testPasswordCompoundContainsExpectedConstraints(): void
    {
        $constraints = $this->getConstraints(new Password());

        self::assertCount(2, $constraints);
        self::assertInstanceOf(NotBlank::class, $constraints[0]);
        self::assertInstanceOf(Length::class, $constraints[1]);
        self::assertSame(UserRules::PASSWORD_MIN, $constraints[1]->min);
        self::assertSame(UserRules::PASSWORD_MAX, $constraints[1]->max);
    }

    public function testCurrentPasswordCompoundContainsExpectedConstraints(): void
    {
        $constraints = $this->getConstraints(new CurrentPassword());

        self::assertCount(2, $constraints);
        self::assertInstanceOf(NotBlank::class, $constraints[0]);
        self::assertInstanceOf(UserPassword::class, $constraints[1]);
        self::assertSame('Current password is incorrect.', $constraints[1]->message);
    }

    public function testEmailTokenCompoundContainsExpectedConstraints(): void
    {
        $constraints = $this->getConstraints(new EmailToken());

        self::assertCount(3, $constraints);
        self::assertInstanceOf(NotBlank::class, $constraints[0]);
        self::assertInstanceOf(Length::class, $constraints[1]);
        self::assertSame(UserRules::TOKEN_MIN, $constraints[1]->min);
        self::assertSame(UserRules::TOKEN_MAX, $constraints[1]->max);
        self::assertInstanceOf(ValidEmailToken::class, $constraints[2]);
    }

    public function testPasswordTokenCompoundContainsExpectedConstraints(): void
    {
        $constraints = $this->getConstraints(new PasswordToken());

        self::assertCount(3, $constraints);
        self::assertInstanceOf(NotBlank::class, $constraints[0]);
        self::assertInstanceOf(Length::class, $constraints[1]);
        self::assertSame(UserRules::TOKEN_MIN, $constraints[1]->min);
        self::assertSame(UserRules::TOKEN_MAX, $constraints[1]->max);
        self::assertInstanceOf(ValidPasswordToken::class, $constraints[2]);
    }

    /**
     * @return array<int, object>
     */
    private function getConstraints(Compound $compound): array
    {
        $method = new \ReflectionMethod($compound, 'getConstraints');

        /** @var array<int, object> $constraints */
        $constraints = $method->invoke($compound, []);

        return $constraints;
    }
}
