<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\TokenCheck;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TokenCheckRequest
{
    public function __construct(
        public string $token,

        #[Assert\Choice(choices: ['email', 'password'], message: 'Invalid token type')]
        public string $type,
    ) {
    }
}
