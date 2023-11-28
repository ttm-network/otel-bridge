<?php

declare(strict_types=1);

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Export\TransportFactoryInterface;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessorFactory;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use Psr\Container\ContainerInterface;

/** @var array $params */
return [
    TracerInterface::class => static function (ContainerInterface $container) use ($params): TracerInterface {
        return $container->get(TracerProviderInterface::class)->getTracer(
            name: $params['ttm/telemetry-otel']['service_name']
        );
    },
    TracerProviderInterface::class => [
        'definition' => static function (ContainerInterface $container): TracerProviderInterface {
            return new TracerProvider($container->get(SpanProcessorInterface::class));
        },
        'reset' => function (): void {
            /** @var TracerProviderInterface $this */
            $this->forceFlush();
            $this->shutdown();
        }
    ],
    SpanExporterInterface::class => static function (ContainerInterface $container) {
        return new SpanExporter($container->get(TransportFactoryInterface::class));
    },
    TransportFactoryInterface::class => static function () use ($params) {
        return (new OtlpHttpTransportFactory())
            ->create(
                endpoint: $params['ttm/telemetry-otel']['endpoint'],
                contentType: $params['ttm/telemetry-otel']['protocol']
            );
    },
    TextMapPropagatorInterface::class => static function () {
        return TraceContextPropagator::getInstance();
    },
    SpanProcessorInterface::class => static function (ContainerInterface $container) {
    return new SimpleSpanProcessor($container->get(SpanExporterInterface::class));
        return (new SpanProcessorFactory())->create($container->get(SpanExporterInterface::class));
    }
];
