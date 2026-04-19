<?php

declare(strict_types=1);

namespace App\Application\Auth\EmailConfirm;

final readonly class EmailConfirmResult
{
    public function __construct(
        public string $email,
        public string $message,
    ) {
    }
}
