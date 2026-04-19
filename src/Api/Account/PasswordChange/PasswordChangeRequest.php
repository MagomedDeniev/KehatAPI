<?php

declare(strict_types=1);

namespace App\Api\Account\PasswordChange;

use App\Validator\Compounds as Compound;

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
