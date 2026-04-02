<?php

namespace App\DTO\Request;

use App\Validator\Compounds as Compound;

final readonly class ForgotPasswordRequest
{
    public function __construct(
        #[Compound\Email]
        public string $email
    ){}
}
