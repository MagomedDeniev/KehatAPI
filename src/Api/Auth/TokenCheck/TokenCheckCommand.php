<?php

declare(strict_types=1);

namespace App\Api\Auth\TokenCheck;

final readonly class TokenCheckCommand
{
    public function __construct(
        public string $token,
        public string $type,
    ) {
    }
}
