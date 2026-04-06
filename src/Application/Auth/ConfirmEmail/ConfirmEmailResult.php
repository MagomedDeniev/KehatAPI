<?php

declare(strict_types=1);

namespace App\Application\Auth\ConfirmEmail;

final readonly class ConfirmEmailResult
{
    public function __construct(
        public string $email,
        public string $message,
    ) {
    }
}
