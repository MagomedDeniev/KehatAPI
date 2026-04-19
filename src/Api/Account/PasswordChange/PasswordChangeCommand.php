<?php

declare(strict_types=1);

namespace App\Api\Account\PasswordChange;

final readonly class PasswordChangeCommand
{
    public function __construct(
        public int $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {
    }
}
