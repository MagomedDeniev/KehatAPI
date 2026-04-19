<?php

declare(strict_types=1);

namespace App\Application\Account\PasswordChange;

final readonly class PasswordChangeResult
{
    public function __construct(
        public string $message,
    ) {
    }
}
