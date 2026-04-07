<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\Rules\UserRules;
use App\Domain\ValueObject\TokenExpirationTime;
use PHPUnit\Framework\TestCase;

final class TokenExpirationTimeTest extends TestCase
{
    public function testItCreatesExpirationUsingConfiguredInterval(): void
    {
        $before = new \DateTimeImmutable();
        $expiration = (new TokenExpirationTime())->value();
        $after = new \DateTimeImmutable();

        $min = $before->modify(sprintf('+%d seconds', UserRules::TOKEN_EXPIRATION_SECONDS - 1));
        $max = $after->modify(sprintf('+%d seconds', UserRules::TOKEN_EXPIRATION_SECONDS + 1));

        self::assertGreaterThanOrEqual($min->getTimestamp(), $expiration->getTimestamp());
        self::assertLessThanOrEqual($max->getTimestamp(), $expiration->getTimestamp());
    }
}
