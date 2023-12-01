<?php

declare(strict_types=1);

namespace TTM\Telemetry\Otel;

use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use TTM\Telemetry\Context\Context;
use TTM\Telemetry\Context\ContextExtractorInterface;

final class ContextExtractor implements ContextExtractorInterface
{
    public function __construct(
        private readonly TextMapPropagatorInterface $propagator,
    ) {
    }

    public function extract(array $data): Context
    {
        $context = $this->propagator->extract($data);
        $carrier = [];
        $this->propagator->inject($carrier, context: $context);

        return new Context($carrier);
    }
}
