<?php
declare(strict_types=1);
/**
 * @author   crayoon
 * @contact  so.wo@foxmail.com
 */

namespace Crayoon\HyperfGrpcClient;

use Exception;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Grpc\StatusCode;
use Hyperf\LoadBalancer\LoadBalancerManager;
use Hyperf\LoadBalancer\Node;
use Hyperf\ServiceGovernance\DriverManager;
use Psr\Container\ContainerInterface;

class GrpcClientManager {
    private string|null $credentials = null;
    private array $pools = [];

    protected DriverManager $governanceManager;
    protected ConfigInterface $config;
    protected StdoutLoggerInterface $logger;

    public function __construct(protected ContainerInterface $container) {
        $this->config            = $this->container->get(ConfigInterface::class);
        $this->governanceManager = $this->container->get(DriverManager::class);
        $this->logger            = $this->container->get(StdoutLoggerInterface::class);
    }

    public function getNode(string $server): string {
        $driverName       = $this->config->get("grpc.register.driver_name", "nacos");
        $consulDriverPath = $driverName == "nacos" ? "" : $this->config->get("services.drivers.consul.uri", "");
        $algo             = $this->config->get("grpc.register.algo", "round-robin");
        if ($governance = $this->governanceManager->get($driverName)) {
            try {
                /**
                 * @var LoadBalancerManager $loadBalancerManager
                 */
                $loadBalancerManager = $this->container->get(LoadBalancerManager::class);
                $serverLB            = $loadBalancerManager->getInstance($server, $algo);
                if (!$serverLB->isAutoRefresh()) {
                    $fun = function () use ($governance, $server, $consulDriverPath) {
                        $nodes = [];
                        foreach ($governance->getNodes($consulDriverPath, $server, ['protocol' => 'grpc']) as $node) {
                            $nodes[] = new Node($node['host'], $node['port'], $node['weight'] ?? 1);
                        }
                        return $nodes;
                    };
                    $serverLB->setNodes($fun())->refresh($fun);
                }
                $node = $serverLB->select();
                return sprintf("%s:%d", $node->host, $node->port);
            } catch (\Throwable $throwable) {
                $this->logger->error(sprintf("Get Node[%s] From %s[%s] Fail! Because:%s", $server, $driverName, $algo, $throwable->getMessage()));
            }
        }
        return "";
    }

    /**
     * get client
     * @param string $hostname
     * @param string $method
     * @return GrpcClient
     * @throws Exception
     */
    public function getClient(string $hostname, string $method): GrpcClient {
        if (empty($hostname)) {
            //获取服务名称
            $server = trim(current(explode(".", $method)), "/");
            //获取节点地址
            $hostname = $this->getNode($server);
        }

        if (empty($hostname)) {
            throw new Exception("hostname not found!");
        }
        if (!isset($this->pools[$hostname])) {
            $this->pools[$hostname] = new GrpcClient($hostname, [
                'credentials' => $this->credentials,
            ]);
        }
        return $this->pools[$hostname];
    }

    public function addClient(string $hostname, GrpcClient $client): void {
        $this->pools[$hostname] = $client;
    }

    public function removeClient(string $hostname, GrpcClient $client): void {
        if (isset($this->pools[$hostname])) {
            unset($this->pools[$hostname]);
        }
    }

    public function invoke(string $hostname, string $method, $argument, $deserialize, array $metadata = [], array $options = []): array {
        //响应
        try {
            return $this->getClient($hostname, $method)->invoke($method, $argument, $deserialize, $metadata, $options);
        } catch (Exception $e) {
            $this->logger->error(sprintf("Client[%s %s] Error:%s", $hostname, $method, $e->getMessage()));
            return [null, StatusCode::ABORTED];
        }
    }
}