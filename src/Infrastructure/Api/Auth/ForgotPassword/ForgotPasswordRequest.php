<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\ForgotPassword;

use App\Infrastructure\Validator\Compounds as Compound;

final readonly class ForgotPasswordRequest
{
    public function __construct(
        #[Compound\SavedEmail]
        public string $email,
    ) {
    }
}
