<?php

declare(strict_types=1);

namespace App\Application\Auth\EmailConfirm;

final readonly class EmailConfirmCommand
{
    public function __construct(
        public string $token,
    ) {
    }
}
