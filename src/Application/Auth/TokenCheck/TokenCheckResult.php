<?php

declare(strict_types=1);

namespace App\Application\Auth\TokenCheck;

final readonly class TokenCheckResult
{
    public function __construct(
        public string $message,
    ) {
    }
}
