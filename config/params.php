<?php

declare(strict_types=1);

use TTM\Telemetry\Otel\SystemClock;
use TTM\Telemetry\Otel\TracerFactory;

return [
    'ttm/telemetry' => [
        'drivers' => [
            'otel' => TracerFactory::class,
        ],
        'dependencies' => [
            'clock' => SystemClock::class
        ]
    ],
    'ttm/telemetry-otel' => [
        'service_name' => 'Yii Framework',
        'endpoint' => $_ENV['OTEL_EXPORTER_OTLP_ENDPOINT'] ?? 'http://collector1:4318/v1/traces',
        'protocol' => $_ENV['OTEL_EXPORTER_OTLP_PROTOCOL'] ?? 'application/json'
    ]
];
