<?php

declare(strict_types=1);

namespace App\Api\Auth\EmailConfirm;

final readonly class EmailConfirmResult
{
    public function __construct(
        public string $message,
    ) {
    }
}
