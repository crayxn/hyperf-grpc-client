<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Crayoon\HyperfGrpcClient;

use Crayoon\HyperfGrpcClient\Command\GenerateCommand;

class ConfigProvider {
    public function __invoke(): array {
        return [
            'dependencies' => [
            ],
            'commands'     => [
                GenerateCommand::class
            ],
            'annotations'  => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish'      => [
                [
                    'id'          => 'config',
                    'description' => 'the config for grpc',
                    'source'      => __DIR__ . '/../publish/grpc.php',
                    'destination' => BASE_PATH . '/config/autoload/grpc.php',
                ],
                [
                    'id'          => 'config',
                    'description' => 'The config for tracer.',
                    'source'      => __DIR__ . '/../publish/opentracing.php',
                    'destination' => BASE_PATH . '/config/autoload/opentracing.php',
                ],
                [
                    'id'          => 'config',
                    'description' => 'The config for services.',
                    'source'      => __DIR__ . '/../publish/services.php',
                    'destination' => BASE_PATH . '/config/autoload/services.php',
                ],
            ]
        ];
    }
}
