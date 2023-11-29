<?php

declare(strict_types=1);

namespace TTM\Telemetry\Otel;

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use TTM\Telemetry\AbstractTracer;
use TTM\Telemetry\ClockInterface;
use TTM\Telemetry\Span;
use TTM\Telemetry\SpanInterface;
use TTM\Telemetry\SpanLink;
use TTM\Telemetry\StackTraceFormatterInterface;
use TTM\Telemetry\TraceKind;
use Yiisoft\Injector\Injector;

final class Tracer extends AbstractTracer
{
    private ?\OpenTelemetry\API\Trace\SpanInterface $lastSpan = null;

    public function __construct(
        Injector $injector,
        private readonly TracerInterface $tracer,
        private readonly TextMapPropagatorInterface $propagator,
        private readonly ClockInterface $clock,
        private readonly StackTraceFormatterInterface $stackTraceFormatter,
        private array $context = []
    ) {
        parent::__construct($injector);
    }

    public function startSpan(
        string $name,
        array $attributes = [],
        bool $scoped = false,
        ?TraceKind $traceKind = null,
        ?int $startTime = null
    ): SpanInterface {
        $span = $this->createInternalSpan(
            name: $name,
            traceKind: $traceKind,
            startTime: $startTime,
            attributes: $attributes
        );

        $link = $this->getTraceSpan($name, $traceKind, $startTime);
        $link->setAttributes($span->getAttributes());

        $scope = null;
        if ($scoped) {
            $scope = $link->activate();
        }

        $this->spans->add($span, new SpanLink($link, $scope));

        return $span;
    }

    public function endSpan(SpanInterface $span): void
    {
        $link = $this->spans->link($span);
        /** @var \OpenTelemetry\API\Trace\SpanInterface $otelSpan */
        $otelSpan = $link->span;

        if (($status = $span->getStatus()) !== null) {
            $otelSpan->setStatus((string)$status->code, $status->description);
        }

        $otelSpan->updateName($span->getName());
        $this->attachEvents($span, $otelSpan);
        $otelSpan->setAttributes($span->getAttributes());

        $link->scope?->detach();
        $otelSpan->end();

        $this->spans->remove($span);
    }

    /**
     * @throws \Throwable
     */
    public function trace(
        string $name,
        callable $callback,
        array $attributes = [],
        bool $scoped = false,
        ?TraceKind $traceKind = null,
        ?int $startTime = null
    ): mixed {
        $otelSpan = $this->getTraceSpan($name, $traceKind, $startTime);
        $internalSpan = $this->createInternalSpan(
            name: $name,
            traceKind: $traceKind,
            startTime: $startTime,
            attributes: $attributes
        );

        $scope = null;
        if ($scoped) {
            $scope = $otelSpan->activate();
        }

        try {
            $result = $this->runScope($internalSpan, $callback);

            if (($status = $internalSpan->getStatus()) !== null) {
                $otelSpan->setStatus((string)$status->code, $status->description);
            }

            return $result;
        } catch (\Throwable $e) {
            $internalSpan->recordException($e);
            throw $e;
        } finally {
            $otelSpan->updateName($internalSpan->getName());
            $this->attachEvents($internalSpan, $otelSpan);
            $otelSpan->setAttributes($internalSpan->getAttributes());

            $scope?->detach();
            $otelSpan->end();
        }
    }

    public function getContext(): array
    {
        if ($this->lastSpan !== null) {
            $ctx = $this->lastSpan->storeInContext(Context::getCurrent());
            $carrier = [];
            $this->propagator->inject($carrier, null, $ctx);

            return $carrier;
        }

        return $this->context;
    }

    public function convertSpanKind(?TraceKind $traceKind): int
    {
        return match ($traceKind) {
            TraceKind::CLIENT => SpanKind::KIND_CLIENT,
            TraceKind::SERVER => SpanKind::KIND_SERVER,
            TraceKind::PRODUCER => SpanKind::KIND_PRODUCER,
            TraceKind::CONSUMER => SpanKind::KIND_CONSUMER,
            default => SpanKind::KIND_INTERNAL
        };
    }

    private function createInternalSpan(
        string $name,
        ?TraceKind $traceKind,
        ?int $startTime,
        array $attributes
    ): Span {
        return new Span(
            name: $name,
            traceKind: $traceKind ?? TraceKind::INTERNAL,
            clock: $this->clock,
            stackTraceFormatter: $this->stackTraceFormatter,
            startEpochNanos: $startTime ?? $this->clock->now(),
            attributes: $attributes
        );
    }

    private function getTraceSpan(
        string $name,
        ?TraceKind $traceKind,
        ?int $startTime
    ): \OpenTelemetry\API\Trace\SpanInterface {
        $spanBuilder = $this->tracer->spanBuilder($name)
            ->setSpanKind($this->convertSpanKind($traceKind));

        if ($startTime !== null) {
            $spanBuilder->setStartTimestamp($startTime);
        }

        if ($this->context !== []) {
            $spanBuilder->setParent(
                $this->propagator->extract($this->context)
            );
        }

        return $this->lastSpan = $spanBuilder->startSpan();
    }

    private function attachEvents(SpanInterface $span, \OpenTelemetry\API\Trace\SpanInterface $otelSpan): void
    {
        foreach ($span->getEvents() as $event) {
            if ($event->getName() === 'exception') {
                $otelSpan->setStatus(StatusCode::STATUS_ERROR);
            }
            $otelSpan->addEvent($event->getName(), $event->getAttributes(), $event->getEpochNanos());
        }
    }
}
