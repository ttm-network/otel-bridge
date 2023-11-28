<?php

declare(strict_types=1);

namespace TTM\Telemetry\Otel;

use OpenTelemetry\SDK\Common\Time\ClockFactory;
use TTM\Telemetry\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): int
    {
        return ClockFactory::getDefault()->now();
    }
}
