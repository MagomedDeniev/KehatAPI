<?php

declare(strict_types=1);

namespace App\Api\Account\PasswordChange;

final readonly class PasswordChangeResult
{
    public function __construct(
        public string $message,
    ) {
    }
}
