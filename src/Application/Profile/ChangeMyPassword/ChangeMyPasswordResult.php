<?php

declare(strict_types=1);

namespace App\Application\Profile\ChangeMyPassword;

final readonly class ChangeMyPasswordResult
{
    public function __construct(
        public string $message,
    ) {
    }
}
