<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\PasswordRestore;

use App\Infrastructure\Validator\Compounds as Compound;

final readonly class PasswordRestoreRequest
{
    public function __construct(
        #[Compound\PasswordToken]
        public string $token,

        #[Compound\Password]
        public string $password,
    ) {
    }
}
