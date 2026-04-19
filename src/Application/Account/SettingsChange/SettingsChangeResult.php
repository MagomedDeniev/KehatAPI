<?php

declare(strict_types=1);

namespace App\Application\Account\SettingsChange;

final readonly class SettingsChangeResult
{
    public function __construct(
        public string $message,
        public bool $emailUpdated,
    ) {
    }
}
