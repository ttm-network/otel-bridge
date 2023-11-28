<?php

declare(strict_types=1);

namespace TTM\Telemetry\Otel;

use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use TTM\Telemetry\ClockInterface;
use TTM\Telemetry\StackTraceFormatterInterface;
use TTM\Telemetry\TracerFactoryInterface;
use TTM\Telemetry\TracerInterface;
use Yiisoft\Injector\Injector;

final class TracerFactory implements TracerFactoryInterface
{
    public function __construct(
        private readonly Injector $injector,
        private readonly \OpenTelemetry\API\Trace\TracerInterface $tracer,
        private readonly TextMapPropagatorInterface $propagator,
        private readonly ClockInterface $clock,
        private readonly StackTraceFormatterInterface $stackTraceFormatter,
    ) {
    }

    public function make(array $context = []): TracerInterface
    {
        $context = \array_intersect_ukey(
            $context,
            \array_flip($this->propagator->fields()),
            fn(string $key1, string $key2): int => (\strtolower($key1) === \strtolower($key2)) ? 0 : -1
        );

        return new Tracer(
            injector: $this->injector,
            tracer: $this->tracer,
            propagator: $this->propagator,
            clock: $this->clock,
            stackTraceFormatter: $this->stackTraceFormatter,
            context: $context
        );
    }
}
