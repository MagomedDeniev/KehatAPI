<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Account\PasswordChange;

use App\Infrastructure\Validator\Compounds as Compound;

final readonly class PasswordChangeRequest
{
    public function __construct(
        #[Compound\CurrentPassword]
        public string $currentPassword,

        #[Compound\Password]
        public string $newPassword,
    ) {
    }
}
