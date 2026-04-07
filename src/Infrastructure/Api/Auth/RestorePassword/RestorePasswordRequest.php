<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\RestorePassword;

use App\Infrastructure\Validator\Compounds as Compound;

final readonly class RestorePasswordRequest
{
    public function __construct(
        #[Compound\PasswordToken]
        public string $token,

        #[Compound\Password]
        public string $newPassword,
    ) {
    }
}
