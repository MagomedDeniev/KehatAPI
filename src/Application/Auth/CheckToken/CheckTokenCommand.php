<?php

declare(strict_types=1);

namespace App\Application\Auth\CheckToken;

final readonly class CheckTokenCommand
{
    public function __construct(
        public string $token,
        public string $type,
    ) {
    }
}
