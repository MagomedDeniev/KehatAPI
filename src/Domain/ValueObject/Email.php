<?php

namespace App\Domain\ValueObject;

use App\Domain\Rules\UserRules;
use InvalidArgumentException;

final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        // Убираем пробелы и приводим к нижнему регистру.
        $normalized = self::normalize($value);

        // Проверяем, что строка не пустая и похожа на email.
        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false || $value === '') {
            throw new \InvalidArgumentException('Email is not valid.');
        }

        if (mb_strlen($normalized) < UserRules::EMAIL_MIN || mb_strlen($normalized) > UserRules::EMAIL_MAX) {
            throw new InvalidArgumentException(sprintf(
                'Email length must be between %d and %d characters.',
                UserRules::EMAIL_MIN,
                UserRules::EMAIL_MAX,
            ));
        }

        $this->value = $normalized;
    }

    public static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public function value(): string
    {
        return $this->value;
    }
}
