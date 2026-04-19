<?php

declare(strict_types=1);

namespace App\Api\Auth\PasswordRestore;

final readonly class PasswordRestoreCommand
{
    public function __construct(
        public string $token,
        public string $password,
    ) {
    }
}
