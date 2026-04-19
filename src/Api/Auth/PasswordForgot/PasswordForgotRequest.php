<?php

declare(strict_types=1);

namespace App\Api\Auth\PasswordForgot;

use App\Validator\Compounds as Compound;

final readonly class PasswordForgotRequest
{
    public function __construct(
        #[Compound\SavedEmail]
        public string $email,
    ) {
    }
}
