<?php

declare(strict_types=1);

namespace App\Application\Auth\PasswordForgot;

final readonly class PasswordForgotResult
{
    public function __construct(
        public string $email,
        public string $message,
    ) {
    }
}
