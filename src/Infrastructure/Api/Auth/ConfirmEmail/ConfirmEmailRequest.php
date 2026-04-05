<?php

namespace App\Infrastructure\Api\Auth\ConfirmEmail;

use App\Infrastructure\Validator\Compounds as Compound;

final readonly class ConfirmEmailRequest
{
    public function __construct(
        #[Compound\EmailToken]
        public string $token,
    ) {}
}
