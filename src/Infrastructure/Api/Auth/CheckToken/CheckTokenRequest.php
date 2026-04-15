<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Auth\CheckToken;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CheckTokenRequest
{
    public function __construct(
        public string $token,

        #[Assert\Choice(choices: ['email', 'password'], message: 'Invalid token type')]
        public string $type,
    ) {
    }
}
