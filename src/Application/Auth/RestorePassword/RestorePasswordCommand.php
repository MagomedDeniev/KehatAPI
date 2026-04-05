<?php

namespace App\Application\Auth\RestorePassword;

final readonly class RestorePasswordCommand
{
    public function __construct(
        public string $token,
        public string $password
    ) {}
}
