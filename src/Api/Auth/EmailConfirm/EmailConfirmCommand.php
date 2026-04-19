<?php

declare(strict_types=1);

namespace App\Api\Auth\EmailConfirm;

final readonly class EmailConfirmCommand
{
    public function __construct(
        public string $token,
    ) {
    }
}
