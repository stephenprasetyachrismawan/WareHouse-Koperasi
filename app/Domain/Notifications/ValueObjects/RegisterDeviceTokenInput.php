<?php

namespace App\Domain\Notifications\ValueObjects;

readonly class RegisterDeviceTokenInput
{
    public function __construct(
        public string $rawToken,
        public string $provider,
        public string $platform,
        public ?string $deviceName = null,
        public ?string $browser = null,
        public ?string $userAgentSummary = null,
    ) {}
}
