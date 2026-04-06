<?php

namespace App\Application\Profile\ChangeMySettings;

final readonly class ChangeMySettingsCommand
{
    public function __construct(
        public int $userId,
        public string $username,
        public string $email
    ) {}
}
