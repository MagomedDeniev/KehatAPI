<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Rules\UserRules;

final readonly class PasswordToken
{
    private string $value;

    public function __construct(string $value)
    {
        if (mb_strlen($value) < UserRules::TOKEN_MIN || mb_strlen($value) > UserRules::TOKEN_MAX) {
            throw new \InvalidArgumentException(sprintf('Password token length must be between %d and %d characters.', UserRules::TOKEN_MIN, UserRules::TOKEN_MAX));
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
