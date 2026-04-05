<?php

namespace App\Application\Profile\ChangeMySettings;

final readonly class ChangeMySettingsResult
{
    public function __construct(
        public string $message
    ) {}
}
