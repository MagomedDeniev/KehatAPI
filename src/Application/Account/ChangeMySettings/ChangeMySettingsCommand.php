<?php

declare(strict_types=1);

namespace App\Application\Account\ChangeMySettings;

use App\Domain\Enum\GenderEnum;

final readonly class ChangeMySettingsCommand
{
    public function __construct(
        public int $userId,
        public string $username,
        public GenderEnum $gender,
        public \DateTimeImmutable $birthDate,
        public string $email,
    ) {
    }
}
