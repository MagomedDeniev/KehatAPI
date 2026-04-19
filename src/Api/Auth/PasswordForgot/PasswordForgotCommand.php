<?php

declare(strict_types=1);

namespace App\Api\Auth\PasswordForgot;

final readonly class PasswordForgotCommand
{
    public function __construct(
        public string $email,
    ) {
    }
}
