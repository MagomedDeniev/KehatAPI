<?php

declare(strict_types=1);

namespace App\Application\Auth\ForgotPassword;

final readonly class ForgotPasswordCommand
{
    public function __construct(
        public string $email,
    ) {
    }
}
