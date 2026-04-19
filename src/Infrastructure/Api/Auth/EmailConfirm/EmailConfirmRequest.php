<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\EmailConfirm;

use App\Infrastructure\Validator\Compounds as Compound;

final readonly class EmailConfirmRequest
{
    public function __construct(
        #[Compound\EmailToken]
        public string $token,
    ) {
    }
}
