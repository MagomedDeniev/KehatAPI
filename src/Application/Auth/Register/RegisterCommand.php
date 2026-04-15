<?php

declare(strict_types=1);

namespace App\Application\Auth\Register;

use App\Domain\Enum\GenderEnum;

final readonly class RegisterCommand
{
    public function __construct(
        public string $username,
        public GenderEnum $gender,
        public \DateTimeImmutable $birthDate,
        public string $email,
        public string $password,
    ) {
    }
}
