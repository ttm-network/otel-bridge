<?php

declare(strict_types=1);

use OpenTelemetry\API\LoggerHolder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Config\Config;

/**
 * @var Config $config
 * @var array $params
 */
return [
    static function (ContainerInterface $container) use ($config, $params) {
        LoggerHolder::set($container->get(LoggerInterface::class));
    },
];
