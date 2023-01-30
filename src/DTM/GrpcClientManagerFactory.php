<?php

namespace Xhtkyy\HyperfTools\Dtm;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Xhtkyy\HyperfTools\App\Container;
use Xhtkyy\HyperfTools\GrpcClient\GrpcClientManager;

class GrpcClientManagerFactory {
    public function __invoke(ContainerInterface $container): GrpcClientManager {
        $config  = $container->get(ConfigInterface::class);
        $manager = new GrpcClientManager(new Container($container));
        //判断是否 需要dtm
        if ($config->has('dtm')) {
            $server   = $config->get('dtm.server', '127.0.0.1');
            $port     = $config->get('dtm.port.grpc', 36790);
            $hostname = $server . ':' . $port;
            $manager->addClient($hostname, new DtmGrpcClient($hostname));
        }
        return $manager;
    }
}