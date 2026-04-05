<?php

namespace App\Application\Profile\ChangeMySettings;

final readonly class ChangeMySettingsCommand
{
    public function __construct(
        public string $userId,
        public string $username,
        public string $email
    ) {}
}
