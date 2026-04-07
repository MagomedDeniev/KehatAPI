<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Rules\UserRules;

final readonly class Username
{
    private string $value;

    public function __construct(string $value)
    {
        // Убираем пробелы по краям.
        $value = trim($value);

        if (mb_strlen($value) < UserRules::USERNAME_MIN || mb_strlen($value) > UserRules::USERNAME_MAX) {
            throw new \InvalidArgumentException(sprintf('Username length must be between %d and %d characters.', UserRules::USERNAME_MIN, UserRules::USERNAME_MAX));
        }

        if (!preg_match(UserRules::USERNAME_PATTERN, $value)) {
            throw new \InvalidArgumentException('Username can contain only letters, numbers, dots, and underscores.');
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
