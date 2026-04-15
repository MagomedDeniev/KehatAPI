<?php

declare(strict_types=1);

namespace App\Application\Auth\CheckToken;

final readonly class CheckTokenResult
{
    public function __construct(
        public string $message
    ) {
    }
}
