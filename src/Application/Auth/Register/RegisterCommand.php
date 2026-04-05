<?php

namespace App\Application\Auth\Register;

final readonly class RegisterCommand
{
    public function __construct(
        public string $email,
        public string $username,
        public string $plainPassword,
    ) {}
}
