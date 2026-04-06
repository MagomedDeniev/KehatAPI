<?php

declare(strict_types=1);

namespace App\Application\Profile\ChangeMyPassword;

final readonly class ChangeMyPasswordCommand
{
    public function __construct(
        public int $userId,
        public string $password,
    ) {
    }
}
