<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\PasswordForgot;

use App\Infrastructure\Validator\Compounds as Compound;

final readonly class PasswordForgotRequest
{
    public function __construct(
        #[Compound\SavedEmail]
        public string $email,
    ) {
    }
}
