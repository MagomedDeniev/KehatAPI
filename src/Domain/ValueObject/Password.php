<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Rules\UserRules;

final readonly class Password
{
    private string $value;

    public function __construct(string $value)
    {
        if (mb_strlen($value) < UserRules::PASSWORD_MIN || mb_strlen($value) > UserRules::PASSWORD_MAX) {
            throw new \InvalidArgumentException(sprintf('Password length must be between %d and %d characters.', UserRules::PASSWORD_MIN, UserRules::PASSWORD_MAX));
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
