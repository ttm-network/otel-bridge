<?php

declare(strict_types=1);

use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use Psr\Container\ContainerInterface;
use TTM\Telemetry\Context;
use Yiisoft\Yii\Http\Event\BeforeRequest;

return [
    BeforeRequest::class => [
        static function (BeforeRequest $event, ContainerInterface $container): void {
            $container->get(Context::class)->setContext(
                $container->get(TextMapPropagatorInterface::class)->extract($event->getRequest()->getHeaders())
            );
        }
    ]
];
