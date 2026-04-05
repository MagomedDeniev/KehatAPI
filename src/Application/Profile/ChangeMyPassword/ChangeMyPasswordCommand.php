<?php

namespace App\Application\Profile\ChangeMyPassword;

final readonly class ChangeMyPasswordCommand
{
    public function __construct(
        public string $userId,
        public string $password
    ) {}
}
