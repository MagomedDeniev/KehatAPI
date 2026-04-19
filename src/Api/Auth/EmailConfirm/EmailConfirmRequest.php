<?php

declare(strict_types=1);

namespace App\Api\Auth\EmailConfirm;

use App\Validator\Compounds as Compound;

final readonly class EmailConfirmRequest
{
    public function __construct(
        #[Compound\EmailToken]
        public string $token,
    ) {
    }
}
