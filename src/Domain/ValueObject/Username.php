<?php

namespace App\Domain\ValueObject;

use App\Domain\Rules\UserRules;
use InvalidArgumentException;

final readonly class Username
{
    private string $value;

    public function __construct(string $value)
    {
        // Убираем пробелы по краям.
        $normalized = self::normalize($value);

        // Проверяем длину.
        if ($normalized === '') {
            throw new InvalidArgumentException('Username cannot be blank.');
        }

        if (mb_strlen($normalized) < UserRules::USERNAME_MIN || mb_strlen($normalized) > UserRules::USERNAME_MAX) {
            throw new InvalidArgumentException(sprintf(
                'Username length must be between %d and %d characters.',
                UserRules::USERNAME_MIN,
                UserRules::USERNAME_MAX,
            ));
        }

        if (!preg_match(UserRules::USERNAME_PATTERN, $normalized)) {
            throw new InvalidArgumentException('Username can contain only letters, numbers, dots, and underscores.');
        }

        $this->value = $normalized;
    }

    public static function normalize(string $value): string
    {
        return trim($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
