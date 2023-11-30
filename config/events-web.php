<?php

declare(strict_types=1);

use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use Psr\Container\ContainerInterface;
use TTM\Telemetry\Context;
use Yiisoft\Yii\Http\Event\BeforeRequest;

return [
    BeforeRequest::class => [
        static function (BeforeRequest $event, ContainerInterface $container): void {
            $propagator = $container->get(TextMapPropagatorInterface::class);
            $context = $propagator->extract($event->getRequest()->getHeaders());
            $carrier = [];
            $propagator->inject($carrier, context: $context);
            $container->get(Context::class)->setContext($carrier);
        }
    ]
];
