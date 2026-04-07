<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Rules\UserRules;

final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        // Убираем пробелы и приводим к нижнему регистру.
        $value = mb_strtolower(trim($value));

        // Проверяем, что строка не пустая и похожа на email.
        if (false === filter_var($value, FILTER_VALIDATE_EMAIL) || '' === $value) {
            throw new \InvalidArgumentException('Email is not valid.');
        }

        if (mb_strlen($value) < UserRules::EMAIL_MIN || mb_strlen($value) > UserRules::EMAIL_MAX) {
            throw new \InvalidArgumentException(sprintf('Email length must be between %d and %d characters.', UserRules::EMAIL_MIN, UserRules::EMAIL_MAX));
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
