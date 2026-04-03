<?php

namespace App\DTO\Request;

use App\Validator\Compounds as Compound;

final readonly class EmailVerifyRequest
{
    public function __construct(
        #[Compound\EmailToken]
        public string $token,
    ){}
}
