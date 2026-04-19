<?php

declare(strict_types=1);

namespace App\Application\Auth\PasswordRestore;

final readonly class PasswordRestoreResult
{
    public function __construct(
        public int $userId,
        public string $message,
    ) {
    }
}
