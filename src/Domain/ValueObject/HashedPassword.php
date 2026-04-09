<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class HashedPassword
{
    private string $value;

    public function __construct(string $value)
    {
        if ('' === $value) {
            throw new \InvalidArgumentException('Hashed password must not be empty.');
        }

        $info = password_get_info($value);

        if (null === $info['algo'] || 0 === $info['algo']) {
            throw new \InvalidArgumentException('Password hash is not valid.');
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
