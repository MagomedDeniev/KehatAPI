<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Rules\UserRules;

final readonly class TokenExpirationTime
{
    private \DateTimeImmutable $value;

    public function __construct()
    {
        $this->value = (new \DateTimeImmutable())->modify(sprintf('+%d seconds', UserRules::TOKEN_EXPIRATION_SECONDS));
    }

    public function value(): \DateTimeImmutable
    {
        return $this->value;
    }
}
