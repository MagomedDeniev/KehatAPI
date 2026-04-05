<?php

namespace App\Application\Auth\Register;

final readonly class RegisterResult
{
    public function __construct(
        public int $userId,
        public string $email,
        public string $message,
    ) {}
}
