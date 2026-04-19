<?php

declare(strict_types=1);

namespace App\Api\Auth\PasswordRestore;

final readonly class PasswordRestoreResult
{
    public function __construct(
        public int $userId,
        public string $message,
    ) {
    }
}
