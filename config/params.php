<?php

declare(strict_types=1);

use TTM\Telemetry\Otel\ContextExtractor;
use TTM\Telemetry\Otel\SystemClock;
use TTM\Telemetry\Otel\Tracer;

return [
    'ttm/telemetry' => [
        'drivers' => [
            'otel' => Tracer::class,
        ],
        'context/extractor' => [
            'extractors' => [
                'otel' => ContextExtractor::class
            ]
        ],
        'registry' => [
            'clock' => SystemClock::class
        ],
    ],
    'ttm/telemetry-otel' => [
        'service_name' => 'Yii Framework',
        'endpoint' => $_ENV['OTEL_EXPORTER_OTLP_ENDPOINT'] ?? 'http://collector:4318/v1/traces',
        'protocol' => $_ENV['OTEL_EXPORTER_OTLP_PROTOCOL'] ?? 'application/json'
    ]
];
