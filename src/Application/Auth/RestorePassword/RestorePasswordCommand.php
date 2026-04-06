<?php

declare(strict_types=1);

namespace App\Application\Auth\RestorePassword;

final readonly class RestorePasswordCommand
{
    public function __construct(
        public string $token,
        public string $password,
    ) {
    }
}
