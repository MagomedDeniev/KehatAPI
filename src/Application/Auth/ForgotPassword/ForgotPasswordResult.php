<?php

namespace App\Application\Auth\ForgotPassword;

final readonly class ForgotPasswordResult
{
    public function __construct(
        public string $email,
        public string $message
    ) {}
}
