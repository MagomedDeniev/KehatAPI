<?php

declare(strict_types=1);

namespace App\Application\Account\ChangeMySettings;

final readonly class ChangeMySettingsResult
{
    public function __construct(
        public string $message,
        public bool $emailUpdated,
    ) {
    }
}
