<?php

declare(strict_types=1);

namespace App\Application\Auth\ConfirmEmail;

final readonly class ConfirmEmailCommand
{
    public function __construct(
        public string $token,
    ) {
    }
}
