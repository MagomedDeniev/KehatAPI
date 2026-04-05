<?php

namespace App\Application\Auth\RestorePassword;

final readonly class RestorePasswordResult
{
    public function __construct(
        public int $userId,
        public string $message,
    ) {}
}
