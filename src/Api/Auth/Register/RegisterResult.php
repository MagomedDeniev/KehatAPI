<?php

declare(strict_types=1);

namespace App\Api\Auth\Register;

final readonly class RegisterResult
{
    public function __construct(
        public int $userId,
        public string $message,
    ) {
    }
}
