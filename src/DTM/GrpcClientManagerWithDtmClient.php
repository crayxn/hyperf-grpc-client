<?php
declare(strict_types=1);
/**
 * @author   crayoon
 * @contact  so.wo@foxmail.com
 */

namespace Crayoon\HyperfGrpcClient\DTM;

use Crayoon\HyperfGrpcClient\GrpcClientManager;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;

class GrpcClientManagerWithDtmClient {
    public function __invoke(ContainerInterface $container): GrpcClientManager {
        $manager = new GrpcClientManager($container);
        try {
            $config   = $container->get(ConfigInterface::class);
            $server   = $config->get('dtm.server', '127.0.0.1');
            $port     = $config->get('dtm.port.grpc', 36790);
            $hostname = $server . ':' . $port;
            $manager->addClient($hostname, new Client($hostname));
        } catch (\Exception $exception) {
            $container->get(StdoutLoggerInterface::class)
                ->error("Dtm Client Create Fail!" . $exception->getMessage());
        }
        return $manager;
    }
}